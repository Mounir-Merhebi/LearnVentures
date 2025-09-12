// src/pages/AIChat/index.jsx
import React, { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Navbar from '../../components/shared/Navbar';
import './AIChat.css';
import { ArrowLeft, Send } from 'lucide-react';

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
      const token = localStorage.getItem('token') || user.token || '';
      const API_BASE = process.env.REACT_APP_API_BASE || 'http://127.0.0.1:8000';

      // Ensure we have a chat session for this user/grade
      let sessionId = localStorage.getItem('chat_session_id');
      if (!sessionId) {
        const gradeId = user.grade_id || 2; // default fallback
        const sessionRes = await fetch(`${API_BASE}/api/v0.1/chat/sessions`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {})
          },
          body: JSON.stringify({ grade_id: gradeId })
        });

        if (!sessionRes.ok) throw new Error('Failed to create chat session');
        const sessionData = await sessionRes.json();
        sessionId = sessionData?.data?.session_id;
        if (sessionId) localStorage.setItem('chat_session_id', sessionId);
      }

      // Send message to backend chat API
      const msgRes = await fetch(`${API_BASE}/api/v0.1/chat/messages`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(token ? { Authorization: `Bearer ${token}` } : {})
        },
        body: JSON.stringify({ session_id: Number(sessionId), message: userMessage.content })
      });

      if (!msgRes.ok) {
        const txt = await msgRes.text();
        console.error('Chat API error', msgRes.status, txt);
        throw new Error('Chat API error');
      }
      const msgData = await msgRes.json();
      const aiText = msgData?.data?.response || "I'm sorry, I couldn't get an answer right now.";

      setMessages(prev => [...prev, {
        id: Date.now() + 1,
        type: 'ai',
        content: aiText,
        timestamp: new Date(),
      }]);
    } catch (err) {
      console.error(err);
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

  // The client no longer calls Gemini directly — requests go through the backend
  // which handles embeddings, retrieval and Gemini calls. This keeps the API key
  // and context management secure on the server.

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
