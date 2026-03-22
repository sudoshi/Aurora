from contextlib import asynccontextmanager

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from .config import settings


@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup
    yield
    # Shutdown


app = FastAPI(
    title=settings.app_name,
    version="2.0.0",
    docs_url="/api/ai/docs",
    openapi_url="/api/ai/openapi.json",
    lifespan=lifespan,
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["https://aurora.acumenus.net", "http://localhost:5175"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Import and include routers
from .routers.health import router as health_router
from .routers.abby import router as abby_router
from .routers.embeddings import router as embeddings_router
from .routers.clinical_nlp import router as clinical_nlp_router
from .routers.decision_support import router as decision_support_router
from .routers.similarity import router as similarity_router
from .routers.copilot import router as copilot_router

app.include_router(health_router, prefix="/api/ai")
app.include_router(abby_router, prefix="/api/ai/abby")
app.include_router(embeddings_router, prefix="/api/ai")
app.include_router(clinical_nlp_router, prefix="/api/ai")
app.include_router(decision_support_router, prefix="/api/ai")
app.include_router(similarity_router, prefix="/api/ai")
app.include_router(copilot_router, prefix="/api/ai")
