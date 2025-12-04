from pydantic_settings import BaseSettings
from typing import List, Union
from pydantic import field_validator


class Settings(BaseSettings):
    """Application settings and configuration"""
    
    # Environment
    environment: str = "development"
    
    # Database
    database_url: str
    
    # Security
    secret_key: str
    algorithm: str = "HS256"
    access_token_expire_minutes: int = 30
    
    # CORS
    allowed_origins: Union[str, List[str]] = "http://localhost:3000,http://localhost:8000"
    
    @field_validator('allowed_origins', mode='before')
    @classmethod
    def parse_origins(cls, v):
        """Parse allowed_origins from string or list"""
        if isinstance(v, str):
            return [origin.strip() for origin in v.split(',')]
        return v
    
    # Upload
    upload_folder: str = "uploads"
    max_upload_size: int = 5242880  # 5MB
    
    # Admin
    admin_email: str = "admin@serein.com"
    
    class Config:
        env_file = ".env"
        case_sensitive = False


settings = Settings()
