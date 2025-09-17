// src/pages/AIChat/index.jsx
import React, { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Navbar from '../../components/shared/Navbar';
import './AIChat.css';
import { ArrowLeft, Send, Mic, MicOff } from 'lucide-react';
import axios from 'axios';

const AIChat = () => {
  const navigate = useNavigate();
  const [messages, setMessages] = useState([]);

  // Initialize greeting message using the logged-in user's name from localStorage
  useEffect(() => {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const name = user?.name || user?.email || 'Student';
    const greeting = `Hi ${name}, I'm Optimus your AI assistant. I'm here to help you with your learning journey! I can assist you with:\n\n• Explaining math concepts and solving problems\n• Providing study tips and learning strategies\n• Helping you understand difficult topics\n• Offering practice exercises and examples\n\nWhat would you like to learn about today?`;
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
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

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
      // Get OpenAI API key from environment variable
      const openaiApiKey = process.env.REACT_APP_OPENAI_API_KEY;

      if (!openaiApiKey) {
        throw new Error('OpenAI API key not configured. Please set REACT_APP_OPENAI_API_KEY in your environment variables.');
      }

      // Prepare conversation history for context
      const conversationHistory = messages.slice(-10).map(msg => ({
        role: msg.type === 'user' ? 'user' : 'assistant',
        content: msg.content
      }));

      // Add the current user message
      conversationHistory.push({
        role: 'user',
        content: userMessage.content
      });

      // Create system prompt for educational AI assistant
      const systemPrompt = {
        role: 'system',
        content: `You are Optimus, a friendly and knowledgeable AI assistant specialized in education and learning. You help students with their studies, particularly in math and academic subjects.

Your personality:
- Friendly and encouraging
- Patient and supportive
- Clear and concise in explanations
- Adapt explanations to the student's level
- Use examples and analogies when helpful
- Encourage critical thinking and problem-solving

Your capabilities:
- Explain math concepts and solve problems
- Provide study tips and learning strategies
- Help with homework and assignments
- Offer practice exercises and examples
- Answer questions about various academic subjects
- Guide students through complex topics step by step

Guidelines:
- Always be encouraging and positive
- Break down complex concepts into smaller, understandable parts
- Use relatable examples and real-world applications
- Ask clarifying questions when needed
- Suggest additional resources when appropriate
- Maintain a supportive and motivating tone

Remember: You're helping students learn and grow, so focus on building their confidence and understanding.`
      };

      const messagesForAPI = [systemPrompt, ...conversationHistory];

      // Call OpenAI API
      const response = await axios.post(
        'https://api.openai.com/v1/chat/completions',
        {
          model: 'gpt-3.5-turbo',
          messages: messagesForAPI,
          max_tokens: 1000,
          temperature: 0.7,
        },
        {
          headers: {
            'Authorization': `Bearer ${openaiApiKey}`,
            'Content-Type': 'application/json',
          },
        }
      );

      const aiText = response.data.choices[0]?.message?.content;

      if (!aiText) {
        throw new Error('No response received from OpenAI');
      }

      setMessages(prev => [...prev, {
        id: Date.now() + 1,
        type: 'ai',
        content: aiText,
        timestamp: new Date(),
      }]);
    } catch (err) {
      console.error('Send message error:', err);

      // Display error message to user
      const errorMessage = err.response?.data?.error?.message ||
                          err.message ||
                          'An unknown error occurred while communicating with the AI assistant';

      setMessages(prev => [...prev, {
        id: Date.now() + 1,
        type: 'ai',
        content: `Error: ${errorMessage}`,
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

        try {
          // send to STT endpoint
          await sendAudioToSTT(blob);
        } catch (sttErr) {
          console.error('STT transcription failed:', sttErr);
          // Show error to user
          setMessages(prev => [...prev, {
            id: Date.now() + 1,
            type: 'ai',
            content: `Speech-to-text error: ${sttErr.message}`,
            timestamp: new Date(),
          }]);
        }

        // stop all tracks
        stream.getTracks().forEach((t) => t.stop());
      });

      mediaRecorder.start();
      setIsRecording(true);
    } catch (err) {
      console.error('Failed to start recording', err);
      throw new Error(`Recording failed: ${err.message}`);
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

      const res = await axios.post(`${API_BASE}/transcribe`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      const data = res.data;
      const text = data.text || data.transcription || '';

      if (!text || !text.trim()) {
        throw new Error('No transcription received from speech-to-text service');
      }

      // append transcription to existing input instead of replacing and do NOT auto-send
      setInputMessage((prev) => (prev && prev.trim() ? `${prev.trim()} ${text}` : text));
    } catch (err) {
      console.error('Transcription error:', err);
      throw err; // Re-throw to let parent handle the error
    } finally {
      setIsTranscribing(false);
    }
  };

  // The client now calls OpenAI API directly using the provided API key
  // This allows for a simpler architecture without backend AI processing

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
