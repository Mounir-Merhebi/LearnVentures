import React, { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Navbar from '../../components/shared/Navbar';
import './AIChat.css';

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
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

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
    const currentMessage = inputMessage.trim();
    setInputMessage('');
    setIsLoading(true);

    try {
      // Get user info from localStorage for personalization
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      
      // Call Gemini AI directly
      const response = await callGeminiAPI(currentMessage, user);
      
      const aiMessage = {
        id: Date.now() + 1,
        type: 'ai',
        content: response,
        timestamp: new Date(),
      };

      setMessages(prev => [...prev, aiMessage]);
    } catch (error) {
      console.error('Error sending message:', error);
      const errorMessage = {
        id: Date.now() + 1,
        type: 'ai',
        content: "I'm sorry, I'm having trouble connecting right now. Please try again later.",
        timestamp: new Date(),
      };
      setMessages(prev => [...prev, errorMessage]);
    } finally {
      setIsLoading(false);
    }
  };

  const callGeminiAPI = async (message, user) => {
    const GEMINI_API_KEY = process.env.REACT_APP_GEMINI_API_KEY;
    
    if (!GEMINI_API_KEY) {
      throw new Error('Gemini API key not configured');
    }

    // Create personalized context based on user profile
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

    const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=${GEMINI_API_KEY}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        contents: [{
          parts: [{
            text: prompt
          }]
        }],
        generationConfig: {
          temperature: 0.7,
          topK: 40,
          topP: 0.95,
          maxOutputTokens: 1000,
        },
        safetySettings: [
          {
            category: "HARM_CATEGORY_HARASSMENT",
            threshold: "BLOCK_MEDIUM_AND_ABOVE"
          },
          {
            category: "HARM_CATEGORY_HATE_SPEECH",
            threshold: "BLOCK_MEDIUM_AND_ABOVE"
          },
          {
            category: "HARM_CATEGORY_SEXUALLY_EXPLICIT",
            threshold: "BLOCK_MEDIUM_AND_ABOVE"
          },
          {
            category: "HARM_CATEGORY_DANGEROUS_CONTENT",
            threshold: "BLOCK_MEDIUM_AND_ABOVE"
          }
        ]
      }),
    });

    if (!response.ok) {
      throw new Error(`Gemini API error: ${response.status}`);
    }

    const data = await response.json();
    
    if (data.candidates && data.candidates[0] && data.candidates[0].content) {
      return data.candidates[0].content.parts[0].text;
    } else {
      throw new Error('Invalid response from Gemini API');
    }
  };

  const handleBackToDashboard = () => {
    navigate('/dashboard');
  };

  return (
    <div className="ai-chat-page">
      <Navbar />
      
      <div className="chat-container">
        {/* Header */}
        <div className="chat-header">
          <button className="back-btn" onClick={handleBackToDashboard}>
            ← Back to Dashboard
          </button>
          <div className="chat-title">
            <div className="ai-avatar">
              <span className="ai-icon">🤖</span>
            </div>
            <h1>Chat with your AI assistant Optimus</h1>
          </div>
        </div>

        {/* Messages Area */}
        <div className="messages-container">
          <div className="messages-list">
            {messages.map((message) => (
              <div
                key={message.id}
                className={`message ${message.type === 'user' ? 'user-message' : 'ai-message'}`}
              >
                <div className="message-content">
                  <p>{message.content}</p>
                  <span className="message-time">
                    {message.timestamp.toLocaleTimeString([], { 
                      hour: '2-digit', 
                      minute: '2-digit' 
                    })}
                  </span>
                </div>
              </div>
            ))}
            
            {isLoading && (
              <div className="message ai-message">
                <div className="message-content">
                  <div className="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                  </div>
                </div>
              </div>
            )}
            
            <div ref={messagesEndRef} />
          </div>
        </div>

        {/* Input Area */}
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
              >
                <svg
                  width="20"
                  height="20"
                  viewBox="0 0 24 24"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M22 2L11 13"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  />
                  <path
                    d="M22 2L15 22L11 13L2 9L22 2Z"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  />
                </svg>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default AIChat;
