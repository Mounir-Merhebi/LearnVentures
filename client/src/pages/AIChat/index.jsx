// src/pages/AIChat/index.jsx
import React, { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Navbar from '../../components/shared/Navbar';
import './AIChat.css';
import { ArrowLeft, Send, Mic, MicOff } from 'lucide-react';

const AIChat = () => {
  const navigate = useNavigate();
  const [messages, setMessages] = useState([]);

  // Initialize greeting message using the logged-in user's name from localStorage
  useEffect(() => {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const name = user?.name || user?.email || 'Student';
    const greeting = `Hi ${name}, I'm Optimus your AI assistant. how can I help you today?`;
    setMessages([
      {
        id: Date.now(),
        type: 'ai',
        content: greeting,
        timestamp: new Date(),
      }
    ]);
  }, []);
  const [inputMessage, setInputMessage] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [isRecording, setIsRecording] = useState(false);
  const mediaRecorderRef = useRef(null);
  const recordedChunksRef = useRef([]);
  const [inputDevices, setInputDevices] = useState([]);
  const [selectedDeviceId, setSelectedDeviceId] = useState('');
  const [isTranscribing, setIsTranscribing] = useState(false);
  
  const messagesEndRef = useRef(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };
  useEffect(() => { scrollToBottom(); }, [messages]);

  useEffect(() => {
    // enumerate audio input devices
    const updateDevices = async () => {
      try {
        // ensure permission to get labels
        await navigator.mediaDevices.getUserMedia({ audio: true }).catch(() => {});
        const devices = await navigator.mediaDevices.enumerateDevices();
        const inputs = devices.filter(d => d.kind === 'audioinput');
        setInputDevices(inputs);
        if (!selectedDeviceId && inputs.length) setSelectedDeviceId(inputs[0].deviceId);
      } catch (err) {
        console.error('Failed to enumerate devices', err);
      }
    };

    updateDevices();
    navigator.mediaDevices.addEventListener('devicechange', updateDevices);
    return () => navigator.mediaDevices.removeEventListener('devicechange', updateDevices);
  }, []);

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

  const startRecording = async () => {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert('Media recording is not supported in this browser');
      return;
    }

    try {
      const constraints = selectedDeviceId
        ? { audio: { deviceId: { exact: selectedDeviceId } } }
        : { audio: true };
      const stream = await navigator.mediaDevices.getUserMedia(constraints);
      recordedChunksRef.current = [];
      const mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
      mediaRecorderRef.current = mediaRecorder;

      mediaRecorder.addEventListener('dataavailable', (e) => {
        if (e.data.size > 0) recordedChunksRef.current.push(e.data);
      });

      mediaRecorder.addEventListener('stop', async () => {
        const blob = new Blob(recordedChunksRef.current, { type: 'audio/webm' });

        // send to STT endpoint
        await sendAudioToSTT(blob);

        // stop all tracks
        stream.getTracks().forEach((t) => t.stop());
      });

      mediaRecorder.start();
      setIsRecording(true);
    } catch (err) {
      console.error('Failed to start recording', err);
      alert('Could not start audio recording. Check microphone permissions.');
    }
  };

  const stopRecording = () => {
    const mediaRecorder = mediaRecorderRef.current;
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
      mediaRecorder.stop();
    }
    mediaRecorderRef.current = null;
    setIsRecording(false);
  };

  const sendAudioToSTT = async (blob) => {
    // use isTranscribing to indicate STT progress; do not toggle isLoading so user can send manually
    setIsTranscribing(true);
    try {
      const API_BASE = process.env.REACT_APP_STT_BASE || 'http://127.0.0.1:6060';
      const form = new FormData();
      form.append('audio', blob, 'recording.webm');

      const res = await fetch(`${API_BASE}/transcribe`, {
        method: 'POST',
        body: form,
      });

      if (!res.ok) {
        const txt = await res.text().catch(() => '');
        console.error('STT service error', res.status, txt);
        throw new Error('STT service error');
      }
      const data = await res.json();
      const text = data.text || data.transcription || '';
      if (text.trim()) {
        // append transcription to existing input instead of replacing and do NOT auto-send
        setInputMessage((prev) => (prev && prev.trim() ? `${prev.trim()} ${text}` : text));
      } else {
        // no transcription returned
        console.warn('No transcription returned', data);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setIsTranscribing(false);
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
                placeholder={isTranscribing ? 'Transcribing...' : 'Ask me anything about your studies...'}
                className="message-input"
                disabled={isLoading}
              />
              <div style={{display: 'flex', gap: 8, alignItems: 'center'}}>
                <select
                  value={selectedDeviceId}
                  onChange={(e) => setSelectedDeviceId(e.target.value)}
                  aria-label="Select microphone"
                  className="mic-select"
                >
                  {inputDevices.length === 0 && <option value="">Default mic</option>}
                  {inputDevices.map(dev => (
                    <option key={dev.deviceId} value={dev.deviceId}>
                      {dev.label ? dev.label.substring(0, 20) + (dev.label.length > 20 ? '...' : '') : 'Microphone'}
                    </option>
                  ))}
                </select>

                <button
                  type="button"
                  className={`record-btn-custom ${isRecording ? 'recording' : ''}`}
                  onClick={() => (isRecording ? stopRecording() : startRecording())}
                  aria-label={isRecording ? 'Stop recording' : 'Start recording'}
                  disabled={isLoading}
                >
                  {isRecording ? <MicOff size={16} /> : <Mic size={16} />}
                  {isRecording ? 'Stop' : 'Record'}
                </button>

                <button
                  type="submit"
                  className="send-btn-rect"
                  disabled={!inputMessage.trim() || isLoading}
                  aria-label="Send message"
                >
                  <Send size={18} />
                </button>
              </div>
            </div>
          </form>
        </div>

        {/* (debug panel removed) */}

      </div>
    </div>
  );
};

export default AIChat;