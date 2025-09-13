from fastapi import FastAPI, UploadFile, File, Form, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from faster_whisper import WhisperModel
import tempfile, os, shutil, logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(title="STT Service")

# Allow the frontend to call this service. Add your frontend origin(s) here.
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:3000", "http://127.0.0.1:3000", "*"] ,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Choose a model: "base" (fast), "small" (better), "medium" (heavier)
# Allow opting out of model init (e.g., for CI) via STT_SKIP_MODEL_INIT=1
_skip_model_init = os.getenv("STT_SKIP_MODEL_INIT", "0") in {"1", "true", "True"}
if _skip_model_init:
    model = None
    logger.info("Skipping WhisperModel initialization due to STT_SKIP_MODEL_INIT")
else:
    try:
        model = WhisperModel("base", compute_type="int8")  # may auto-download model files
    except Exception as e:
        logger.exception("Failed to load WhisperModel: %s", e)
        model = None


@app.get("/health")
async def health():
    return {"status": "ok", "model_loaded": model is not None}


@app.post("/transcribe")
async def transcribe(audio: UploadFile = File(...), language: str | None = Form(None)):
    if model is None:
        raise HTTPException(status_code=503, detail="Transcription model not loaded")

    # Accept uploaded file and write to a temp file (await read to support async upload)
    suffix = os.path.splitext(audio.filename or "")[1] or ".webm"
    temp_path = None
    try:
        content = await audio.read()
        with tempfile.NamedTemporaryFile(suffix=suffix, delete=False) as tmp:
            tmp.write(content)
            temp_path = tmp.name

        # Transcribe using faster-whisper (ffmpeg must be available on the machine)
        segments, info = model.transcribe(temp_path, language=language, vad_filter=True)
        text = " ".join(getattr(seg, 'text', '') for seg in segments).strip()

        return {"text": text, "transcription": text}

    except Exception as e:
        logger.exception("Error during transcription: %s", e)
        raise HTTPException(status_code=500, detail=str(e))

    finally:
        try:
            if temp_path and os.path.exists(temp_path):
                os.remove(temp_path)
        except Exception:
            logger.exception("Failed to remove temp file %s", temp_path)
