// src/pages/AIChat/index.jsx
import React, { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Navbar from '../../components/shared/Navbar';
import './AIChat.css';
import { Bot, ArrowLeft, Send } from 'lucide-react';

const AIChat = () => {
  const navigate = useNavigate();
  const [messages, setMessages] = useState([
    {
      id: 1,
      type: 'ai',
      content: "Hi Mounir, I'm Optimus your AI assistant how can I help you today?",
      timestamp: new Date(),
    }
  ]);
  const [inputMessage, setInputMessage] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const messagesEndRef = useRef(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };
  useEffect(() => { scrollToBottom(); }, [messages]);

  const handleSendMessage = async (e) => {
    e.preventDefault();
    if (!inputMessage.trim() || isLoading) return;

    const userMessage = {
      id: Date.now(),
      type: 'user',
      content: inputMessage.trim(),
      timestamp: new Date(),
    };

    setMessages(prev => [...prev, userMessage]);
    setInputMessage('');
    setIsLoading(true);

    try {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      const response = await callGeminiAPI(userMessage.content, user);
      setMessages(prev => [...prev, {
        id: Date.now() + 1,
        type: 'ai',
        content: response,
        timestamp: new Date(),
      }]);
    } catch {
      setMessages(prev => [...prev, {
        id: Date.now() + 1,
        type: 'ai',
        content: "I'm sorry, I'm having trouble connecting right now. Please try again later.",
        timestamp: new Date(),
      }]);
    } finally {
      setIsLoading(false);
    }
  };

  const callGeminiAPI = async (message, user) => {
    const GEMINI_API_KEY = process.env.REACT_APP_GEMINI_API_KEY;
    if (!GEMINI_API_KEY) throw new Error('Gemini API key not configured');

    const userName = user.name || 'Moon';
    const userHobbies = user.hobbies || 'gaming';
    const userPreferences = user.preferences || 'interactive learning';
    const userBio = user.bio || 'i love programming and playing games';

    const systemPrompt = `You are Optimus, an AI learning assistant for LearnVentures. You are chatting with ${userName}.

User Profile:
- Name: ${userName}
- Hobbies: ${userHobbies}
- Learning Preferences: ${userPreferences}
- Bio: ${userBio}

Your personality:
- Friendly and encouraging
- Educational and helpful
- Personalized responses based on user's interests
- Focus on learning and academic growth
- Incorporate user's hobbies into explanations when relevant

Respond to the user's message in a helpful, personalized way. Keep responses conversational but educational.`;

    const prompt = `${systemPrompt}\n\nUser message: ${message}\n\nResponse:`;

    const res = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=${GEMINI_API_KEY}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        contents: [{ parts: [{ text: prompt }] }],
        generationConfig: { temperature: 0.7, topK: 40, topP: 0.95, maxOutputTokens: 1000 },
        safetySettings: [
          { category: 'HARM_CATEGORY_HARASSMENT', threshold: 'BLOCK_MEDIUM_AND_ABOVE' },
          { category: 'HARM_CATEGORY_HATE_SPEECH', threshold: 'BLOCK_MEDIUM_AND_ABOVE' },
          { category: 'HARM_CATEGORY_SEXUALLY_EXPLICIT', threshold: 'BLOCK_MEDIUM_AND_ABOVE' },
          { category: 'HARM_CATEGORY_DANGEROUS_CONTENT', threshold: 'BLOCK_MEDIUM_AND_ABOVE' }
        ]
      }),
    });
    if (!res.ok) throw new Error(`Gemini API error: ${res.status}`);
    const data = await res.json();
    return data?.candidates?.[0]?.content?.parts?.[0]?.text ?? ' ';
  };

  return (
    <div className="ai-chat-page">
      <Navbar />

      <div className="chat-container">
        {/* Floating robot image (PNG from public/images/robot.png) */}
        <div className="robot-follower" aria-hidden="true">
          <img
            className="robot-img"
            src={`${process.env.PUBLIC_URL}/images/robot.png`}
            alt=""
          />
        </div>

        {/* Header */}
        <div className="chat-header">
          <button className="back-btn" onClick={() => navigate('/dashboard')}>
            <ArrowLeft size={16} /> Back to Dashboard
          </button>
          <div className="chat-title">
          <img
            className="ai-avatar"
            src={`${process.env.PUBLIC_URL}/images/robot.png`}
            alt=""
          />
            <h1>Chat with your AI assistant Optimus</h1>
          </div>
        </div>

        {/* Messages Area */}
        <div className="messages-container">
          <div className="messages-list">
            {messages.map((m) => (
              <div key={m.id} className={`message ${m.type === 'user' ? 'user-message' : 'ai-message'}`}>
                <div className="message-content">
                  <p>{m.content}</p>
                  <span className="message-time">
                    {m.timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                  </span>
                </div>
              </div>
            ))}

            {isLoading && (
              <div className="message ai-message">
                <div className="message-content">
                  <div className="typing-indicator"><span></span><span></span><span></span></div>
                </div>
              </div>
            )}

            <div ref={messagesEndRef} />
          </div>
        </div>

        {/* Input */}
        <div className="input-container">
          <form onSubmit={handleSendMessage} className="input-form">
            <div className="input-wrapper">
              <input
                type="text"
                value={inputMessage}
                onChange={(e) => setInputMessage(e.target.value)}
                placeholder="Ask me anything about your studies..."
                className="message-input"
                disabled={isLoading}
              />
              <button
                type="submit"
                className="send-btn"
                disabled={!inputMessage.trim() || isLoading}
                aria-label="Send message"
              >
                <Send size={20} />
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default AIChat;
