from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    app_name: str = "Aurora AI (Abby)"
    debug: bool = False
    database_url: str = "postgresql://smudoshi:acumenus@localhost:5432/aurora"
    ollama_base_url: str = "http://localhost:11434"
    ollama_model: str = "MedAIBase/MedGemma1.5:4b"

    class Config:
        env_file = ".env"


settings = Settings()
