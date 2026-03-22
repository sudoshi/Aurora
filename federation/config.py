"""
Federation relay configuration.

All settings can be overridden via environment variables with the
FEDERATION_ prefix (e.g., FEDERATION_PORT=8200).
"""

from pydantic_settings import BaseSettings


class FederationSettings(BaseSettings):
    app_name: str = "Aurora Federation Relay"
    host: str = "0.0.0.0"
    port: int = 8200

    # mTLS
    tls_cert_path: str = ""
    tls_key_path: str = ""
    tls_ca_cert_path: str = ""  # CA bundle for verifying peer certificates
    require_mtls: bool = True

    # Registry
    registry_file: str = "registry.json"

    # Relay
    relay_timeout: int = 30
    max_peers: int = 50
    max_query_fan_out: int = 10

    # Security
    allowed_query_types: list[str] = ["similarity", "aggregate_stats"]
    max_results_per_peer: int = 100
    min_k_anonymity: int = 5  # Minimum patients before returning aggregate

    class Config:
        env_file = ".env"
        env_prefix = "FEDERATION_"


settings = FederationSettings()
