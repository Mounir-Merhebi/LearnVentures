import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import './Subject.css';
import Navbar from '../../components/shared/Navbar/Navbar';
import API from '../../services/axios';

const Subject = () => {
  const navigate = useNavigate();

  const { subjectId } = useParams();

  const [subjectData, setSubjectData] = useState({
    subject: subjectId ? `Subject ${subjectId}` : 'Subject',
    icon: '📐',
    totalChapters: 0,
    completedChapters: 0,
    chapters: [],
  });

  useEffect(() => {
    const fetchChapters = async () => {
      if (!subjectId) return;
      try {
        // fetch subject metadata first to display the proper name as soon as possible
        let subjectName = null;
        try {
          const metaRes = await API.get(`/subjects/${subjectId}`);
          const meta = metaRes.data || {};
          subjectName = meta.subject_name || meta.name || meta.title || null;
        } catch (metaErr) {
          // ignore meta fetch error and continue to chapters
        }

        const res = await API.get(`/subjects/${subjectId}/chapters`);
        const data = res.data || {};
        // try to get subject name from chapters response if available
        subjectName = subjectName || data.subject_name || (data.subject && (data.subject.name || data.subject.title)) || data.name || data.title;
        const chapters = (data.chapters || []).map((c, idx) => ({
          id: c.id,
          title: c.title,
          chapterNumber: c.order ?? c.chapter_number ?? idx + 1,
          isCompleted: false,
          thumbnail: c.thumbnail || '/images/math-video-thumbnail.jpg',
          duration: c.duration ?? null,
          // prefer lessons array length, fall back to lessons_count fields
          lessons: Array.isArray(c.lessons) ? c.lessons.length : (c.lessons_count ?? c.lessonsCount ?? 0),
        }));

        setSubjectData({
          subject: subjectName ?? `Subject ${subjectId}`,
          icon: '📐',
          totalChapters: chapters.length,
          completedChapters: chapters.filter(ch => ch.isCompleted).length,
          chapters,
        });

        // If API didn't include lesson counts, fetch per-chapter details for missing counts
        const chaptersMissingCounts = chapters.filter(ch => !ch.lessons || ch.lessons === 0);
        if (chaptersMissingCounts.length > 0) {
          try {
            const counts = await Promise.all(chaptersMissingCounts.map(async (ch) => {
              const r = await API.get(`/chapters/${ch.id}`);
              const d = r.data || {};
              return { id: ch.id, lessons: Array.isArray(d.lessons) ? d.lessons.length : (d.lessons_count ?? d.lessonsCount ?? 0) };
            }));

            setSubjectData(prev => ({
              ...prev,
              chapters: prev.chapters.map(ch => {
                const found = counts.find(c => c.id === ch.id);
                return found ? { ...ch, lessons: found.lessons } : ch;
              }),
            }));
          } catch (err) {
            console.warn('Failed to fetch per-chapter lesson counts', err);
          }
        }
      } catch (err) {
        console.error('Failed to fetch chapters for subject', err);
      }
    };

    fetchChapters();
  }, [subjectId]);

  const handleBackToDashboard = () => {
    navigate('/student_dashboard');
  };

  const handleChapterClick = (chapter) => {
    // Navigate to specific chapter within this subject
    navigate(`/subjects/${subjectId}/chapter/${chapter.id}`);
  };

  return (
    <div className="subject-page">
      <Navbar />
      
      <div className="subject-container">
        {/* Header Section */}
        <div className="subject-header">
          <button 
            className="back-button"
            onClick={handleBackToDashboard}
          >
            <span className="back-arrow">←</span>
            Back to Dashboard
          </button>
          
          <div className="subject-info">
            <div className="subject-icon-large">{subjectData.icon}</div>
            <div className="subject-details">
              <h1 className="subject-title">{subjectData.subject}</h1>
              <p className="subject-progress">
                {subjectData.completedChapters} of {subjectData.totalChapters} chapters completed
              </p>
            </div>
          </div>
        </div>

        {/* Chapters Grid */}
        <div className="chapters-container">
          <div className="chapters-grid">
            {subjectData.chapters.map((chapter, index) => (
              <div 
                key={chapter.id} 
                className={`chapter-card ${chapter.isCompleted ? 'completed' : ''}`}
                onClick={() => handleChapterClick(chapter)}
              >
                {/* Video Thumbnail */}
                <div className="chapter-thumbnail">
                  <img className="chapter-image" src={chapter.thumbnail} alt={chapter.title} />
                </div>

                {/* Chapter Info */}
                <div className="chapter-info">
                  <div className="chapter-meta-top">
                    <span className={`chapter-status ${chapter.isCompleted ? 'completed' : 'pending'}`}>
                      CHAPTER {chapter.chapterNumber}
                    </span>
                    <span className={`completion-status ${chapter.isCompleted ? 'completed' : 'pending'}`}>
                      {chapter.isCompleted ? 'COMPLETED' : 'PENDING'}
                    </span>
                  </div>

                  <h3 className="chapter-title">{chapter.title}</h3>
                  <div className="chapter-meta">
                    <span className="chapter-duration">{chapter.duration}</span>
                    <span className="chapter-lessons">{chapter.lessons} lessons</span>
                  </div>
                </div>

                {/* Continue Button */}
                <button className="continue-button">
                  {chapter.isCompleted ? 'REVIEW' : 'CONTINUE'}
                </button>
              </div>
            ))}
          </div>

          {/* Navigation Arrows */}
          <button className="nav-arrow nav-arrow-left">
            <span>‹</span>
          </button>
          <button className="nav-arrow nav-arrow-right">
            <span>›</span>
          </button>
        </div>
      </div>
    </div>
  );
};

export default Subject;


