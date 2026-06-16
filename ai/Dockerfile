# Aurora AI ("Abby") service.
# Pinned to Python 3.12: the ai/ dependency set (pydantic-core, numpy, psycopg2-binary,
# biomcp-python, …) ships cp312 wheels but NOT cp314, so the host's bare Python 3.14
# cannot install them without source builds. The container is the supported runtime.
FROM python:3.12-slim

WORKDIR /app

ENV PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    PIP_DISABLE_PIP_VERSION_CHECK=1

COPY requirements.txt ./
RUN pip install --upgrade pip && pip install -r requirements.txt

COPY app ./app
COPY tests ./tests
COPY pytest.ini ./

EXPOSE 8100

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8100"]
