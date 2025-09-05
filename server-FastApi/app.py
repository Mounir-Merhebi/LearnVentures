import os
import json
import re
from typing import List, Optional
from fastapi import FastAPI, HTTPException, Header, Request
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from dotenv import load_dotenv
import google.generativeai as genai

load_dotenv()
GEMINI_KEY = os.getenv("GEMINI_API_KEY")
SHARED_TOKEN = os.getenv("AI_AGENT_SHARED_TOKEN")
if not GEMINI_KEY:
    raise RuntimeError("GEMINI_API_KEY not set")

genai.configure(api_key=GEMINI_KEY)
MODEL_NAME = "gemini-1.5-flash"

app = FastAPI(title="AlignPath AI Agent")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class CareerPath(BaseModel):
    title: str
    description: str

class CareerResponse(BaseModel):
    career_paths: List[CareerPath] = Field(default_factory=list)

class Quest(BaseModel):
    path_name: str
    title: str
    subtitle: str
    difficulty: str
    duration: str

class QuestResponse(BaseModel):
    quests: List[Quest] = Field(default_factory=list)

class WrongAnswer(BaseModel):
    question: str
    user_answer: str
    correct_answer: str
    lesson_topic: str

class StudyRecommendation(BaseModel):
    topic: str
    reason: str
    priority: str  # high, medium, low
    suggested_resources: List[str] = Field(default_factory=list)
    practice_exercises: List[str] = Field(default_factory=list)

class UserPreferences(BaseModel):
    hobbies: str
    preferred_learning_style: str
    bio: str

class LessonInput(BaseModel):
    lesson_text: str

class PersonalizedLesson(BaseModel):
    title: str
    personalized_content: str
    learning_approach: str
    practical_examples: List[str] = Field(default_factory=list)
    next_steps: List[str] = Field(default_factory=list)

class PersonalizedLessonResponse(BaseModel):
    lesson: PersonalizedLesson

class StudyAnalysisResponse(BaseModel):
    overall_performance: str
    weak_areas: List[str] = Field(default_factory=list)
    recommendations: List[StudyRecommendation] = Field(default_factory=list)
    study_plan: str

def extract_json(text: str):
    """Try to robustly pull JSON out of Gemini response."""
    try:
        return json.loads(text)
    except Exception:
        pass
    fence = re.search(r"json\s*(\{.*?\})\s*", text, flags=re.S)
    if fence:
        try:
            return json.loads(fence.group(1))
        except Exception:
            pass
    blob = re.search(r"(\{.*\})", text, flags=re.S)
    if blob:
        try:
            return json.loads(blob.group(1))
        except Exception:
            pass
    return None

def call_gemini(prompt: str) -> str:
    model = genai.GenerativeModel(MODEL_NAME)
    resp = model.generate_content(prompt)
    if not getattr(resp, "text", None):
        raise HTTPException(status_code=502, detail="Gemini returned empty response")
    return resp.text

def verify_shared_token(header_val: Optional[str]):
    if not SHARED_TOKEN:
        return
    if header_val != f"Bearer {SHARED_TOKEN}":
        raise HTTPException(status_code=401, detail="Unauthorized")

@app.get("/health")
def health():
    return {"ok": True}

@app.post("/analyze-performance", response_model=StudyAnalysisResponse)
async def analyze_performance(wrong_answers: List[WrongAnswer], authorization: Optional[str] = Header(None)):
    verify_shared_token(authorization)

    # Format the wrong answers for the prompt
    answers_text = ""
    for i, answer in enumerate(wrong_answers, 1):
        answers_text += f"""
Question {i}:
- Topic: {answer.lesson_topic}
- Question: {answer.question}
- User's Answer: {answer.user_answer}
- Correct Answer: {answer.correct_answer}
"""

    prompt = f"""
You are an expert educational AI that analyzes student performance and provides personalized study recommendations.

Wrong Answers Analysis:
{answers_text}

Task:
Analyze these wrong answers and provide:
1. Overall performance assessment
2. Identify weak areas and knowledge gaps
3. Suggest specific topics to focus on or revise
4. Create a personalized study plan
5. Provide specific resources and practice exercises

Respond in pure JSON matching this schema:

{{
  "overall_performance": "Brief assessment of overall performance (e.g., 'Good understanding of basics but needs work on advanced concepts')",
  "weak_areas": [
    "Area 1 that needs improvement",
    "Area 2 that needs improvement",
    "Area 3 that needs improvement"
  ],
  "recommendations": [
    {{
      "topic": "Specific topic to focus on",
      "reason": "Why this topic needs attention based on wrong answers",
      "priority": "high|medium|low",
      "suggested_resources": [
        "Resource 1 for this topic",
        "Resource 2 for this topic"
      ],
      "practice_exercises": [
        "Exercise 1 to practice this topic",
        "Exercise 2 to practice this topic"
      ]
    }}
  ],
  "study_plan": "A step-by-step study plan to address the identified weaknesses"
}}
"""
    text = call_gemini(prompt)
    data = extract_json(text) or {
        "overall_performance": "Analysis could not be completed",
        "weak_areas": [],
        "recommendations": [],
        "study_plan": "Please try again with more specific wrong answers"
    }
    return StudyAnalysisResponse(**data)

@app.post("/personalize-lesson", response_model=PersonalizedLessonResponse)
async def personalize_lesson(
    preferences: UserPreferences, 
    lesson: LessonInput, 
    authorization: Optional[str] = Header(None)
):
    verify_shared_token(authorization)

    prompt = f"""
You are an expert educational AI that personalizes lessons based on user preferences.

User Profile:
- Hobbies: {preferences.hobbies}
- Preferred Learning Style: {preferences.preferred_learning_style}
- Bio: {preferences.bio}

Original Lesson:
{lesson.lesson_text}

Task:
Create a personalized version of this lesson that:
1. Adapts the content to match their learning style
2. Incorporates examples from their hobbies and interests
3. Uses language and references that resonate with their background
4. Provides practical examples they can relate to
5. Suggests next steps based on their profile

Respond in pure JSON matching this schema:

{{
  "lesson": {{
    "title": "Personalized lesson title",
    "personalized_content": "The main lesson content adapted to their preferences",
    "learning_approach": "Explanation of how this lesson is tailored to their learning style",
    "practical_examples": [
      "Example 1 related to their hobbies",
      "Example 2 related to their interests",
      "Example 3 that connects to their background"
    ],
    "next_steps": [
      "Step 1 based on their profile",
      "Step 2 tailored to their interests",
      "Step 3 that builds on their background"
    ]
  }}
}}
"""
    text = call_gemini(prompt)
    data = extract_json(text) or {"lesson": {"title": "Personalized Lesson", "personalized_content": "Content could not be personalized", "learning_approach": "Standard approach", "practical_examples": [], "next_steps": []}}
    return PersonalizedLessonResponse(**data)

