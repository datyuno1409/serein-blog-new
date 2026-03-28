"""
Main FastAPI Application Entry Point
Serein Blog Platform - Python/FastAPI Version
"""
import sys
import os

# Add project root to path (not backend directory)
sys.path.insert(0, os.path.dirname(__file__))

from backend.app import app

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=8000,
        reload=True
    )
