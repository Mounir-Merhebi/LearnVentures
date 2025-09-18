import React, { useEffect, useState } from 'react';
import API from '../../services/axios';
import Navbar from '../../components/shared/Navbar/Navbar';
import './Profile.css';

const Profile = () => {
  const [form, setForm] = useState({ name: '', hobbies: '', preferences: '', bio: '' });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    const load = async () => {
      try {
        setLoading(true);
        const res = await API.get('/profile/me');
        if (res.data.success) {
          const d = res.data.data;
          setForm({
            name: d.name || '',
            hobbies: d.hobbies || '',
            preferences: d.preferences || '',
            bio: d.bio || ''
          });
        }
      } catch (e) {
        setError('Failed to load profile');
      } finally {
        setLoading(false);
      }
    };
    load();
  }, []);

  const onChange = (e) => {
    const { name, value } = e.target;
    setForm(prev => ({ ...prev, [name]: value }));
  };

  const onSave = async () => {
    try {
      setSaving(true);
      setMessage(null);
      setError(null);
      const payload = {
        name: form.name,
        hobbies: form.hobbies,
        preferences: form.preferences,
        bio: form.bio,
      };
      const res = await API.put('/profile', payload);
      if (res.data.success) {
        setMessage('Saved. Your personalized lessons will update next time you open them.');
      } else {
        setError(res.data.message || 'Failed to save');
      }
    } catch (e) {
      setError(e.response?.data?.message || 'Failed to save');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="profile-page">
        <Navbar />
        <div className="loading-container">
          <p className="loading-text">Loading your profile...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="profile-page">
      <Navbar />
      <div className="profile-container">
        <div className="profile-header">
          <h1 className="profile-title">My Profile</h1>
          <p className="profile-subtitle">Customize your learning experience</p>
        </div>

        <div className="profile-card">
          {message && <div className="alert alert-success">{message}</div>}
          {error && <div className="alert alert-error">{error}</div>}

          <div className="form-grid">
            <div className="form-group">
              <label className="form-label">Name</label>
              <input 
                className="form-input"
                name="name" 
                value={form.name} 
                onChange={onChange} 
                placeholder="Your full name" 
              />
            </div>
            
            <div className="form-group">
              <label className="form-label">Hobbies & Interests</label>
              <textarea 
                className="form-textarea"
                name="hobbies" 
                value={form.hobbies} 
                onChange={onChange} 
                placeholder="e.g., football, coding, music, reading, gaming..." 
                rows={3} 
              />
            </div>
            
            <div className="form-group">
              <label className="form-label">Learning Preferences</label>
              <textarea 
                className="form-textarea"
                name="preferences" 
                value={form.preferences} 
                onChange={onChange} 
                placeholder="e.g., prefers visual explanations, real-world examples, step-by-step guides..." 
                rows={3} 
              />
            </div>
            
            <div className="form-group">
              <label className="form-label">About Me</label>
              <textarea 
                className="form-textarea"
                name="bio" 
                value={form.bio} 
                onChange={onChange} 
                placeholder="Tell us more about yourself, your goals, and what motivates you to learn..." 
                rows={4} 
              />
            </div>
          </div>

          <div className="form-actions">
            <button className="save-button" onClick={onSave} disabled={saving}>
              {saving ? 'Saving Changes...' : 'Save Profile'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Profile;


