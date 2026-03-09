from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from .config import settings

app = FastAPI(
    title=settings.app_name,
    version="2.0.0",
    docs_url="/api/ai/docs",
    openapi_url="/api/ai/openapi.json",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["https://aurora.acumenus.net", "http://localhost:5175"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/api/ai/health")
async def health():
    return {"status": "ok", "service": "abby", "version": "2.0.0"}
