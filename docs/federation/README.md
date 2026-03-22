# Aurora Federation Guide

Federation enables opt-in cross-institution data sharing for clinical case collaboration. It is **disabled by default** and requires explicit activation at every level (system, institution, case).

## Architecture Overview

```
 Institution A                          Institution B
 +--------------+                       +--------------+
 |  Aurora API  |<-- mTLS + JWT ------->|  Aurora API  |
 |              |                       |              |
 |  Local DB    |                       |  Local DB    |
 +--------------+                       +--------------+
       |                                       |
       +----------- Federation Broker ---------+
                    (optional hub)
```

### Design Principles

1. **Opt-in at every level** -- Federation is off by default. Admins enable it system-wide, then per-institution, then per-case.
2. **Data stays local** -- Patient data never leaves the origin institution. Only de-identified case summaries and decision metadata are shared.
3. **Mutual TLS** -- All federation traffic uses certificate-pinned mTLS connections.
4. **Signed payloads** -- Every federated message is signed with the origin institution's private key.
5. **Audit everything** -- All federated queries and responses are logged in the audit trail.

## Certificate Generation

Each Aurora instance needs a unique certificate pair for federation identity.

```bash
# Generate a private key
openssl genrsa -out federation.key 4096

# Generate a Certificate Signing Request
openssl req -new -key federation.key -out federation.csr \
  -subj "/CN=aurora.your-institution.org/O=Your Institution/C=US"

# Self-sign (for development) or submit CSR to your CA
openssl x509 -req -in federation.csr -signkey federation.key \
  -out federation.crt -days 365 -sha256

# Store in a secure location
mkdir -p storage/federation/certs
mv federation.key federation.crt storage/federation/certs/
chmod 600 storage/federation/certs/federation.key
```

### Environment Configuration

```env
FEDERATION_ENABLED=false
FEDERATION_CERT_PATH=storage/federation/certs/federation.crt
FEDERATION_KEY_PATH=storage/federation/certs/federation.key
FEDERATION_INSTITUTION_ID=your-institution-uuid
FEDERATION_BROKER_URL=https://federation.acumenus.net
```

## Peer Registration

Before two institutions can communicate, they must exchange certificates and register as peers.

### 1. Export Your Public Certificate

```bash
# Share this file with the peer institution (NOT the .key file)
cat storage/federation/certs/federation.crt
```

### 2. Register a Peer

```bash
# Via admin API
curl -X POST https://aurora.your-institution.org/api/admin/federation/peers \
  -H "Authorization: Bearer $TOKEN" \
  -F "name=Partner Hospital" \
  -F "endpoint=https://aurora.partner-hospital.org/api/federation" \
  -F "certificate=@partner-hospital.crt"
```

### 3. Accept Peer Request (on the other side)

The peer institution must also register your certificate and approve the connection.

## Query Flow

```
1. Clinician creates federated case query
   |
2. Aurora validates permissions (user role + case federation flag)
   |
3. Request signed with institution private key
   |
4. mTLS connection established to peer
   |
5. Peer validates certificate + signature
   |
6. Peer checks its own federation policies
   |
7. Peer returns de-identified response
   |
8. Origin Aurora logs the exchange in audit trail
   |
9. Results displayed to clinician (with source attribution)
```

### Request Format

```json
{
  "query_id": "uuid",
  "origin_institution": "uuid",
  "query_type": "case_match",
  "parameters": {
    "specialty": "oncology",
    "conditions": ["BRAF V600E melanoma"],
    "intent": "treatment_precedent"
  },
  "signature": "base64-encoded-signature",
  "timestamp": "2026-03-21T12:00:00Z"
}
```

### Response Format

```json
{
  "query_id": "uuid",
  "responding_institution": "uuid",
  "matches": [
    {
      "case_summary": "De-identified case description...",
      "outcome": "Complete response after combination immunotherapy",
      "decision_type": "treatment_recommendation",
      "specialty": "oncology"
    }
  ],
  "match_count": 1,
  "signature": "base64-encoded-signature",
  "timestamp": "2026-03-21T12:00:01Z"
}
```

## Security Model

| Layer | Mechanism |
|-------|-----------|
| Transport | mTLS with certificate pinning |
| Authentication | Institution certificate identity |
| Authorization | Peer allowlist + per-case federation flag |
| Integrity | RSA signature on every payload |
| Confidentiality | TLS 1.3 encryption in transit |
| Data Minimization | Only de-identified summaries shared |
| Audit | Every query/response logged with full metadata |

### Threat Mitigations

- **Unauthorized access** -- Only registered and mutually-authenticated peers can communicate.
- **Data leakage** -- PHI never leaves the origin; only de-identified summaries cross the wire.
- **Replay attacks** -- Timestamps and query IDs prevent replay; responses expire after 5 minutes.
- **Certificate compromise** -- Immediate revocation via admin panel; peers reject revoked certs.
- **Man-in-the-middle** -- Certificate pinning prevents interception even with compromised CAs.

## Privacy Guarantees

1. **No PHI in transit** -- Patient identifiers (name, MRN, DOB) are stripped before any federated response.
2. **Opt-in consent chain** -- System admin enables federation, institution admin approves peers, case owner enables federation per case.
3. **Right to disconnect** -- Any institution can revoke federation at any time; all cached peer data is purged.
4. **Audit transparency** -- Users can see which cases were queried by which peers and when.
5. **Data retention limits** -- Federated query results are ephemeral and not stored beyond the session.
