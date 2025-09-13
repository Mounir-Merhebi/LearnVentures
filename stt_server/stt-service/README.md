# Speech-to-Text Service

A FastAPI-based service for transcribing audio files to text using Google's Speech Recognition API.

## Features

- Transcribe audio files (WAV, MP3, FLAC, OGG, M4A)
- Support for multiple languages
- RESTful API endpoints
- CORS enabled for web integration

## Setup

1. Create a virtual environment:
```bash
python -m venv .venv
```

2. Activate the virtual environment:
```bash
# On Windows
.venv\Scripts\activate
# On macOS/Linux
source .venv/bin/activate
```

3. Install dependencies:
```bash
pip install -r requirements.txt
```

## Running the Service

From the `stt_server/stt-service` directory:

```bash
uvicorn stt_server:app --host 127.0.0.1 --port 6060 --reload
```

## API Endpoints

### POST /transcribe
Transcribe an audio file to text.

**Parameters:**
- `file`: Audio file upload (multipart/form-data)
- `language`: Language code (optional, default: "en-US")

**Response:**
```json
{
  "success": true,
  "transcription": "Hello, this is a test transcription.",
  "language": "en-US",
  "confidence": 0.8
}
```

### GET /health
Health check endpoint.

### GET /
Basic service information.

## Supported Languages

The service supports any language code supported by Google's Speech Recognition API, such as:
- `en-US` (English - US)
- `en-GB` (English - UK)
- `es-ES` (Spanish - Spain)
- `fr-FR` (French - France)
- `de-DE` (German - Germany)
- And many more...

## Dependencies

- FastAPI: Web framework
- SpeechRecognition: Google Speech API integration
- Uvicorn: ASGI server
- Python-multipart: File upload handling
- Pydantic: Data validation

## Note

This service requires an internet connection to use Google's Speech Recognition API. For offline transcription, consider using other speech recognition engines.
