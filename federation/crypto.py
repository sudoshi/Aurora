"""
Cryptographic operations for federation:
- mTLS certificate validation
- Message signing and verification (Ed25519)
- Institution identity management
"""

import hashlib
import logging
from dataclasses import dataclass
from datetime import datetime, timezone

logger = logging.getLogger(__name__)

try:
    from cryptography import x509
    from cryptography.exceptions import InvalidSignature
    from cryptography.hazmat.primitives import hashes, serialization
    from cryptography.hazmat.primitives.asymmetric.ed25519 import (
        Ed25519PrivateKey,
        Ed25519PublicKey,
    )
    from cryptography.x509.oid import NameOID

    _CRYPTO_AVAILABLE = True
except ImportError:
    _CRYPTO_AVAILABLE = False
    logger.warning(
        "cryptography library not installed; "
        "federation crypto operations will be unavailable"
    )


def _require_crypto() -> None:
    """Raise if the cryptography library is not installed."""
    if not _CRYPTO_AVAILABLE:
        raise RuntimeError(
            "The 'cryptography' package is required for federation crypto. "
            "Install it with: pip install cryptography"
        )


@dataclass(frozen=True)
class PeerIdentity:
    """Identity extracted from a validated peer certificate."""

    institution_id: str
    institution_name: str
    common_name: str
    not_valid_after: datetime
    fingerprint_sha256: str


def validate_peer_certificate(cert_pem: str, ca_bundle: str) -> PeerIdentity:
    """Validate a peer certificate against the CA bundle and extract identity.

    Args:
        cert_pem: PEM-encoded peer certificate.
        ca_bundle: PEM-encoded CA certificate(s) for trust verification.

    Returns:
        PeerIdentity with institution details extracted from the certificate.

    Raises:
        ValueError: If the certificate is invalid, expired, or untrusted.
        RuntimeError: If the cryptography library is unavailable.
    """
    _require_crypto()

    try:
        peer_cert = x509.load_pem_x509_certificate(cert_pem.encode())
    except Exception as exc:
        raise ValueError(f"Failed to parse peer certificate: {exc}") from exc

    try:
        ca_cert = x509.load_pem_x509_certificate(ca_bundle.encode())
    except Exception as exc:
        raise ValueError(f"Failed to parse CA certificate: {exc}") from exc

    # Check expiration
    now = datetime.now(timezone.utc)
    if now > peer_cert.not_valid_after_utc:
        raise ValueError(
            f"Peer certificate expired at {peer_cert.not_valid_after_utc}"
        )
    if now < peer_cert.not_valid_before_utc:
        raise ValueError(
            f"Peer certificate not yet valid (starts {peer_cert.not_valid_before_utc})"
        )

    # Verify the peer cert was signed by the CA
    try:
        ca_public_key = ca_cert.public_key()
        ca_public_key.verify(
            peer_cert.signature,
            peer_cert.tbs_certificate_bytes,
            peer_cert.signature_hash_algorithm,
        )
    except Exception as exc:
        raise ValueError(
            f"Peer certificate not signed by trusted CA: {exc}"
        ) from exc

    # Extract identity fields
    subject = peer_cert.subject
    common_name = _get_name_attribute(subject, NameOID.COMMON_NAME, "unknown")
    org_name = _get_name_attribute(subject, NameOID.ORGANIZATION_NAME, "unknown")

    # Use Organization Unit as institution_id, fall back to CN
    org_unit = _get_name_attribute(
        subject, NameOID.ORGANIZATIONAL_UNIT_NAME, ""
    )
    institution_id = org_unit if org_unit else common_name

    fingerprint = peer_cert.fingerprint(hashes.SHA256()).hex()

    return PeerIdentity(
        institution_id=institution_id,
        institution_name=org_name,
        common_name=common_name,
        not_valid_after=peer_cert.not_valid_after_utc,
        fingerprint_sha256=fingerprint,
    )


def _get_name_attribute(name: "x509.Name", oid: "x509.ObjectIdentifier", default: str) -> str:
    """Safely extract a name attribute from an X.509 Name."""
    attrs = name.get_attributes_for_oid(oid)
    if attrs:
        return attrs[0].value
    return default


def generate_institution_keypair() -> tuple[bytes, bytes]:
    """Generate an Ed25519 key pair for message signing.

    Returns:
        (private_key_bytes, public_key_bytes) in raw format.
    """
    _require_crypto()

    private_key = Ed25519PrivateKey.generate()
    private_bytes = private_key.private_bytes(
        encoding=serialization.Encoding.Raw,
        format=serialization.PrivateFormat.Raw,
        encryption_algorithm=serialization.NoEncryption(),
    )
    public_bytes = private_key.public_key().public_bytes(
        encoding=serialization.Encoding.Raw,
        format=serialization.PublicFormat.Raw,
    )
    return private_bytes, public_bytes


def sign_message(message: bytes, private_key: bytes) -> bytes:
    """Sign a message using Ed25519.

    Args:
        message: The message bytes to sign.
        private_key: 32-byte Ed25519 private key (raw format).

    Returns:
        64-byte Ed25519 signature.
    """
    _require_crypto()

    key = Ed25519PrivateKey.from_private_bytes(private_key)
    return key.sign(message)


def verify_signature(message: bytes, signature: bytes, public_key: bytes) -> bool:
    """Verify an Ed25519 signature.

    Args:
        message: The original message bytes.
        signature: 64-byte Ed25519 signature.
        public_key: 32-byte Ed25519 public key (raw format).

    Returns:
        True if the signature is valid, False otherwise.
    """
    _require_crypto()

    key = Ed25519PublicKey.from_public_bytes(public_key)
    try:
        key.verify(signature, message)
        return True
    except InvalidSignature:
        return False


def hash_patient_id(patient_id: int, institution_id: str) -> str:
    """Create a one-way hash of a patient ID for de-identification.

    Combines the patient_id with the institution_id as a salt to produce
    a deterministic but irreversible identifier. The same patient at the
    same institution always produces the same hash, but the original
    patient_id cannot be recovered.

    Args:
        patient_id: The institution-local patient ID.
        institution_id: The institution's unique identifier (used as salt).

    Returns:
        Hex-encoded SHA-256 hash string.
    """
    payload = f"{institution_id}:{patient_id}".encode()
    return hashlib.sha256(payload).hexdigest()
