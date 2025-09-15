import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import './AdminContentManagement.css';
import Navbar from '../../components/shared/Navbar/Navbar';
import ConfirmationPopup from '../../components/shared/ConfirmationPopup';
import API from '../../services/axios';
import {
  Plus,
  Edit2,
  Trash2,
  BookOpen,
  FileText,
  ChevronDown,
  ChevronRight,
  CheckCircle,
  XCircle
} from 'lucide-react';

const AdminContentManagement = () => {
  const navigate = useNavigate();
  const [subjects, setSubjects] = useState([]);
  const [grades, setGrades] = useState([]);
  const [instructors, setInstructors] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [expandedSubjects, setExpandedSubjects] = useState(new Set());
  const [expandedChapters, setExpandedChapters] = useState(new Set());

  // Modal states
  const [showSubjectModal, setShowSubjectModal] = useState(false);
  const [showChapterModal, setShowChapterModal] = useState(false);
  const [showLessonModal, setShowLessonModal] = useState(false);
  const [showQuizModal, setShowQuizModal] = useState(false);
  const [editingItem, setEditingItem] = useState(null);

  // Confirmation popup state
  const [confirmationPopup, setConfirmationPopup] = useState({
    isOpen: false,
    title: '',
    message: '',
    onConfirm: null,
    onCancel: null
  });

  // Form states
  const [subjectForm, setSubjectForm] = useState({ grade_id: '', instructor_id: '', title: '', description: '' });
  const [chapterForm, setChapterForm] = useState({ subject_id: '', title: '', description: '', cover_photo: '' });
  const [lessonForm, setLessonForm] = useState({ chapter_id: '', title: '', content: '', concept_slug: '' });
  const [quizForm, setQuizForm] = useState({
    chapter_id: '',
    title: '',
    time_limit_seconds: '',
    questions: [{ body: '', options_json: '[]', correct_option: '', order: 1 }]
  });

  useEffect(() => {
    fetchInitialData();
  }, []);

  const fetchInitialData = async () => {
    try {
      setLoading(true);
      // Fetch subjects, grades, and instructors in parallel
      const [subjectsResponse, gradesResponse, instructorsResponse] = await Promise.all([
        API.get('/admin/content/subjects'),
        API.get('/admin/grades'),
        API.get('/admin/instructors')
      ]);

      if (subjectsResponse.data.success) {
        setSubjects(subjectsResponse.data.data);
      }

      if (gradesResponse.data.success) {
        setGrades(gradesResponse.data.data);
      }

      if (instructorsResponse.data.success) {
        setInstructors(instructorsResponse.data.data);
      }
    } catch (err) {
      setError('Failed to fetch content');
      console.error('Error fetching data:', err);
    } finally {
      setLoading(false);
    }
  };


  // Subject operations
  const handleCreateSubject = async () => {
    try {
      const response = await API.post('/admin/content/subjects', subjectForm);
      if (response.data.success) {
        setSubjects([...subjects, response.data.data]);
        setShowSubjectModal(false);
        setSubjectForm({ grade_id: '', instructor_id: '', title: '', description: '' });
      }
    } catch (err) {
      console.error('Error creating subject:', err);
    }
  };

  const handleUpdateSubject = async () => {
    try {
      const response = await API.put(`/admin/content/subjects/${editingItem.id}`, subjectForm);
      if (response.data.success) {
        setSubjects(subjects.map(s => s.id === editingItem.id ? response.data.data : s));
        setShowSubjectModal(false);
        setEditingItem(null);
        setSubjectForm({ grade_id: '', instructor_id: '', title: '', description: '' });
      }
    } catch (err) {
      console.error('Error updating subject:', err);
    }
  };

  const handleDeleteSubject = async (subjectId) => {
    showConfirmation(
      'Delete Subject',
      'Are you sure you want to delete this subject? This will also delete all chapters, lessons, and quizzes within it.',
      async () => {
        try {
          await API.delete(`/admin/content/subjects/${subjectId}`);
          setSubjects(subjects.filter(s => s.id !== subjectId));
          hideConfirmation();
        } catch (err) {
          console.error('Error deleting subject:', err);
        }
      },
      hideConfirmation
    );
  };

  // Chapter operations
  const handleCreateChapter = async () => {
    try {
      const response = await API.post('/admin/content/chapters', chapterForm);
      if (response.data.success) {
        setSubjects(subjects.map(subject =>
          subject.id === parseInt(chapterForm.subject_id)
            ? { ...subject, chapters: [...(subject.chapters || []), response.data.data] }
            : subject
        ));
        setShowChapterModal(false);
        setChapterForm({ subject_id: '', title: '', description: '', cover_photo: '' });
      }
    } catch (err) {
      console.error('Error creating chapter:', err);
    }
  };

  const handleDeleteChapter = async (chapterId) => {
    showConfirmation(
      'Delete Chapter',
      'Are you sure you want to delete this chapter? This will also delete all lessons and quizzes within it.',
      async () => {
        try {
          await API.delete(`/admin/content/chapters/${chapterId}`);
          setSubjects(subjects.map(subject => ({
            ...subject,
            chapters: subject.chapters?.filter(c => c.id !== chapterId) || []
          })));
          hideConfirmation();
        } catch (err) {
          console.error('Error deleting chapter:', err);
        }
      },
      hideConfirmation
    );
  };

  // Lesson operations
  const handleCreateLesson = async () => {
    try {
      const response = await API.post('/admin/content/lessons', lessonForm);
      if (response.data.success) {
        setSubjects(subjects.map(subject => ({
          ...subject,
          chapters: subject.chapters?.map(chapter =>
            chapter.id === parseInt(lessonForm.chapter_id)
              ? { ...chapter, lessons: [...(chapter.lessons || []), response.data.data] }
              : chapter
          ) || []
        })));
        setShowLessonModal(false);
        setLessonForm({ chapter_id: '', title: '', content: '', concept_slug: '' });
      }
    } catch (err) {
      console.error('Error creating lesson:', err);
    }
  };

  const handleDeleteLesson = async (lessonId) => {
    showConfirmation(
      'Delete Lesson',
      'Are you sure you want to delete this lesson?',
      async () => {
        try {
          await API.delete(`/admin/content/lessons/${lessonId}`);
          setSubjects(subjects.map(subject => ({
            ...subject,
            chapters: subject.chapters?.map(chapter => ({
              ...chapter,
              lessons: chapter.lessons?.filter(l => l.id !== lessonId) || []
            })) || []
          })));
          hideConfirmation();
        } catch (err) {
          console.error('Error deleting lesson:', err);
        }
      },
      hideConfirmation
    );
  };

  // Quiz operations
  const handleCreateQuiz = async () => {
    try {
      const response = await API.post('/admin/content/quizzes', quizForm);
      if (response.data.success) {
        // Add quiz to the appropriate chapter
        setSubjects(subjects.map(subject => ({
          ...subject,
          chapters: subject.chapters?.map(chapter =>
            chapter.id === parseInt(quizForm.chapter_id)
              ? { ...chapter, quiz: response.data.data }
              : chapter
          ) || []
        })));
        setShowQuizModal(false);
        setQuizForm({
          chapter_id: '',
          title: '',
          time_limit_seconds: '',
          questions: [{ body: '', options_json: '[]', correct_option: '', order: 1 }]
        });
      }
    } catch (err) {
      console.error('Error creating quiz:', err);
    }
  };

  const handleDeleteQuiz = async (quizId) => {
    showConfirmation(
      'Delete Quiz',
      'Are you sure you want to delete this quiz?',
      async () => {
        try {
          await API.delete(`/admin/content/quizzes/${quizId}`);
          setSubjects(subjects.map(subject => ({
            ...subject,
            chapters: subject.chapters?.map(chapter =>
              chapter.quiz?.id === quizId ? { ...chapter, quiz: null } : chapter
            ) || []
          })));
          hideConfirmation();
        } catch (err) {
          console.error('Error deleting quiz:', err);
        }
      },
      hideConfirmation
    );
  };

  // Confirmation popup helpers
  const showConfirmation = (title, message, onConfirm, onCancel = () => {}) => {
    setConfirmationPopup({
      isOpen: true,
      title,
      message,
      onConfirm,
      onCancel
    });
  };

  const hideConfirmation = () => {
    setConfirmationPopup({
      isOpen: false,
      title: '',
      message: '',
      onConfirm: null,
      onCancel: null
    });
  };

  // Modal handlers
  const openSubjectModal = (subject = null) => {
    if (subject) {
      setEditingItem(subject);
      setSubjectForm({
        grade_id: subject.grade_id || '',
        instructor_id: subject.instructor_id || '',
        title: subject.title,
        description: subject.description || ''
      });
    } else {
      setEditingItem(null);
      setSubjectForm({ grade_id: '', instructor_id: '', title: '', description: '' });
    }
    setShowSubjectModal(true);
  };

  const openChapterModal = (subjectId) => {
    setChapterForm({ ...chapterForm, subject_id: subjectId });
    setShowChapterModal(true);
  };

  const openLessonModal = (chapterId) => {
    setLessonForm({ ...lessonForm, chapter_id: chapterId });
    setShowLessonModal(true);
  };

  const openQuizModal = (chapterId) => {
    setQuizForm({ ...quizForm, chapter_id: chapterId });
    setShowQuizModal(true);
  };

  const addQuizQuestion = () => {
    setQuizForm({
      ...quizForm,
      questions: [...quizForm.questions, { body: '', options_json: '[]', correct_option: '', order: quizForm.questions.length + 1 }]
    });
  };

  const updateQuizQuestion = (index, field, value) => {
    const updatedQuestions = [...quizForm.questions];
    updatedQuestions[index][field] = value;
    setQuizForm({ ...quizForm, questions: updatedQuestions });
  };

  const removeQuizQuestion = (index) => {
    setQuizForm({
      ...quizForm,
      questions: quizForm.questions.filter((_, i) => i !== index)
    });
  };

  const toggleSubject = (subjectId) => {
    const newExpanded = new Set(expandedSubjects);
    if (newExpanded.has(subjectId)) {
      newExpanded.delete(subjectId);
    } else {
      newExpanded.add(subjectId);
    }
    setExpandedSubjects(newExpanded);
  };

  const toggleChapter = (chapterId) => {
    const newExpanded = new Set(expandedChapters);
    if (newExpanded.has(chapterId)) {
      newExpanded.delete(chapterId);
    } else {
      newExpanded.add(chapterId);
    }
    setExpandedChapters(newExpanded);
  };

  if (loading) {
    return (
      <div className="admin-content-page">
        <Navbar />
        <div className="admin-content-container">
          <div className="loading">Loading content management...</div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="admin-content-page">
        <Navbar />
        <div className="admin-content-container">
          <div className="error">
            <h2>Error</h2>
            <p>{error}</p>
            <button onClick={() => navigate('/admin/dashboard')}>Back to Dashboard</button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="admin-content-page">
      <Navbar />

      <div className="admin-navigation">
        <div className="nav-section">
          <button
            className={`nav-btn ${window.location.pathname === '/admin/dashboard' ? 'active' : ''}`}
            onClick={() => navigate('/admin/dashboard')}
          >
            Excel Moderation
          </button>
          <button
            className={`nav-btn ${window.location.pathname === '/admin/content' ? 'active' : ''}`}
            onClick={() => navigate('/admin/content')}
          >
            Content Management
          </button>
        </div>
      </div>

      <div className="admin-content-container">
        <div className="admin-content-header">
          <h1>Content Management</h1>
          <button className="btn-primary" onClick={() => openSubjectModal()}>
            <Plus size={16} />
            Add Subject
          </button>
        </div>

        <div className="content-tree">
          {subjects.map(subject => (
            <div key={subject.id} className="subject-item">
              <div className="subject-header" onClick={() => toggleSubject(subject.id)}>
                {expandedSubjects.has(subject.id) ? <ChevronDown size={16} /> : <ChevronRight size={16} />}
                <BookOpen size={18} />
                <span className="subject-title">{subject.title}</span>
                <div className="subject-actions">
                  <button onClick={(e) => { e.stopPropagation(); openSubjectModal(subject); }}>
                    <Edit2 size={14} />
                  </button>
                  <button onClick={(e) => { e.stopPropagation(); handleDeleteSubject(subject.id); }}>
                    <Trash2 size={14} />
                  </button>
                  <button onClick={(e) => { e.stopPropagation(); openChapterModal(subject.id); }}>
                    <Plus size={14} />
                  </button>
                </div>
              </div>

              {expandedSubjects.has(subject.id) && (
                <div className="subject-content">
                  {subject.description && <p className="subject-description">{subject.description}</p>}

                  {subject.chapters && subject.chapters.map(chapter => (
                    <div key={chapter.id} className="chapter-item">
                      <div className="chapter-header" onClick={() => toggleChapter(chapter.id)}>
                        {expandedChapters.has(chapter.id) ? <ChevronDown size={14} /> : <ChevronRight size={14} />}
                        <FileText size={16} />
                        <span className="chapter-title">{chapter.title}</span>
                        <div className="chapter-actions">
                          <button onClick={(e) => { e.stopPropagation(); openLessonModal(chapter.id); }}>
                            <Plus size={12} />
                          </button>
                          <button onClick={(e) => { e.stopPropagation(); handleDeleteChapter(chapter.id); }}>
                            <Trash2 size={12} />
                          </button>
                        </div>
                      </div>

                      {expandedChapters.has(chapter.id) && (
                        <div className="chapter-content">
                          {chapter.description && <p className="chapter-description">{chapter.description}</p>}

                          {/* Lessons */}
                          {chapter.lessons && chapter.lessons.length > 0 && (
                            <div className="lessons-section">
                              <h4>Lessons</h4>
                              {chapter.lessons.map(lesson => (
                                <div key={lesson.id} className="lesson-item">
                                  <span>{lesson.title}</span>
                                  <button onClick={() => handleDeleteLesson(lesson.id)}>
                                    <Trash2 size={12} />
                                  </button>
                                </div>
                              ))}
                            </div>
                          )}

                          {/* Quiz */}
                          <div className="quiz-section">
                            <h4>Quiz</h4>
                            {chapter.quiz ? (
                              <div className="quiz-item">
                                <CheckCircle size={14} color="#10B981" />
                                <span>{chapter.quiz.title} ({chapter.quiz.question_count} questions)</span>
                                <button onClick={() => handleDeleteQuiz(chapter.quiz.id)}>
                                  <Trash2 size={12} />
                                </button>
                              </div>
                            ) : (
                              <div className="no-quiz">
                                <XCircle size={14} color="#6B7280" />
                                <span>No quiz created</span>
                                <button onClick={() => openQuizModal(chapter.id)}>
                                  <Plus size={12} />
                                </button>
                              </div>
                            )}
                          </div>
                        </div>
                      )}
                    </div>
                  ))}

                  {(!subject.chapters || subject.chapters.length === 0) && (
                    <div className="empty-state">
                      <p>No chapters yet. Click the + button to add a chapter.</p>
                    </div>
                  )}
                </div>
              )}
            </div>
          ))}

          {subjects.length === 0 && (
            <div className="empty-state">
              <p>No subjects yet. Click "Add Subject" to get started.</p>
            </div>
          )}
        </div>

        {/* Subject Modal */}
        {showSubjectModal && (
          <div className="modal-overlay">
            <div className="modal">
              <h3>{editingItem ? 'Edit Subject' : 'Add Subject'}</h3>
              <div className="form-group">
                <label>Grade</label>
                <select
                  value={subjectForm.grade_id}
                  onChange={(e) => setSubjectForm({ ...subjectForm, grade_id: e.target.value })}
                  disabled={editingItem} // Don't allow changing grade when editing
                >
                  <option value="">Select a grade</option>
                  {grades.map(grade => (
                    <option key={grade.id} value={grade.id}>{grade.name}</option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label>Instructor</label>
                <select
                  value={subjectForm.instructor_id}
                  onChange={(e) => setSubjectForm({ ...subjectForm, instructor_id: e.target.value })}
                  disabled={editingItem} // Don't allow changing instructor when editing
                >
                  <option value="">Select an instructor</option>
                  {instructors.map(instructor => (
                    <option key={instructor.id} value={instructor.id}>{instructor.name}</option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label>Title</label>
                <input
                  type="text"
                  value={subjectForm.title}
                  onChange={(e) => setSubjectForm({ ...subjectForm, title: e.target.value })}
                  placeholder="Enter subject title"
                />
              </div>
              <div className="form-group">
                <label>Description (optional)</label>
                <textarea
                  value={subjectForm.description}
                  onChange={(e) => setSubjectForm({ ...subjectForm, description: e.target.value })}
                  placeholder="Enter subject description"
                  rows={3}
                />
              </div>
              <div className="modal-actions">
                <button onClick={() => setShowSubjectModal(false)}>Cancel</button>
                <button
                  className="btn-primary"
                  onClick={editingItem ? handleUpdateSubject : handleCreateSubject}
                >
                  {editingItem ? 'Update' : 'Create'}
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Chapter Modal */}
        {showChapterModal && (
          <div className="modal-overlay">
            <div className="modal">
              <h3>Add Chapter</h3>
              <div className="form-group">
                <label>Title</label>
                <input
                  type="text"
                  value={chapterForm.title}
                  onChange={(e) => setChapterForm({ ...chapterForm, title: e.target.value })}
                  placeholder="Enter chapter title"
                />
              </div>
              <div className="form-group">
                <label>Description (optional)</label>
                <textarea
                  value={chapterForm.description}
                  onChange={(e) => setChapterForm({ ...chapterForm, description: e.target.value })}
                  placeholder="Enter chapter description"
                  rows={3}
                />
              </div>
              <div className="form-group">
                <label>Cover Photo URL (optional)</label>
                <input
                  type="url"
                  value={chapterForm.cover_photo}
                  onChange={(e) => setChapterForm({ ...chapterForm, cover_photo: e.target.value })}
                  placeholder="Enter cover photo URL"
                />
              </div>
              <div className="modal-actions">
                <button onClick={() => setShowChapterModal(false)}>Cancel</button>
                <button className="btn-primary" onClick={handleCreateChapter}>Create</button>
              </div>
            </div>
          </div>
        )}

        {/* Lesson Modal */}
        {showLessonModal && (
          <div className="modal-overlay">
            <div className="modal">
              <h3>Add Lesson</h3>
              <div className="form-group">
                <label>Title</label>
                <input
                  type="text"
                  value={lessonForm.title}
                  onChange={(e) => setLessonForm({ ...lessonForm, title: e.target.value })}
                  placeholder="Enter lesson title"
                />
              </div>
              <div className="form-group">
                <label>Content</label>
                <textarea
                  value={lessonForm.content}
                  onChange={(e) => setLessonForm({ ...lessonForm, content: e.target.value })}
                  placeholder="Enter lesson content"
                  rows={8}
                />
              </div>
              <div className="form-group">
                <label>Concept Slug (optional)</label>
                <input
                  type="text"
                  value={lessonForm.concept_slug}
                  onChange={(e) => setLessonForm({ ...lessonForm, concept_slug: e.target.value })}
                  placeholder="Enter concept slug for AI matching"
                />
              </div>
              <div className="modal-actions">
                <button onClick={() => setShowLessonModal(false)}>Cancel</button>
                <button className="btn-primary" onClick={handleCreateLesson}>Create</button>
              </div>
            </div>
          </div>
        )}

        {/* Quiz Modal */}
        {showQuizModal && (
          <div className="modal-overlay">
            <div className="modal quiz-modal">
              <h3>Create Quiz</h3>
              <div className="form-group">
                <label>Title</label>
                <input
                  type="text"
                  value={quizForm.title}
                  onChange={(e) => setQuizForm({ ...quizForm, title: e.target.value })}
                  placeholder="Enter quiz title"
                />
              </div>
              <div className="form-group">
                <label>Time Limit (seconds, optional)</label>
                <input
                  type="number"
                  value={quizForm.time_limit_seconds}
                  onChange={(e) => setQuizForm({ ...quizForm, time_limit_seconds: e.target.value })}
                  placeholder="Enter time limit in seconds"
                />
              </div>

              <div className="questions-section">
                <h4>Questions</h4>
                {quizForm.questions.map((question, index) => (
                  <div key={index} className="question-item">
                    <div className="question-header">
                      <span>Question {index + 1}</span>
                      {quizForm.questions.length > 1 && (
                        <button onClick={() => removeQuizQuestion(index)}>
                          <XCircle size={14} />
                        </button>
                      )}
                    </div>
                    <textarea
                      placeholder="Enter question"
                      value={question.body}
                      onChange={(e) => updateQuizQuestion(index, 'body', e.target.value)}
                      rows={2}
                    />
                    <textarea
                      placeholder='Enter options as JSON array: ["Option A", "Option B", "Option C", "Option D"]'
                      value={question.options_json}
                      onChange={(e) => updateQuizQuestion(index, 'options_json', e.target.value)}
                      rows={2}
                    />
                    <input
                      type="text"
                      placeholder="Correct option (e.g., Option A)"
                      value={question.correct_option}
                      onChange={(e) => updateQuizQuestion(index, 'correct_option', e.target.value)}
                    />
                  </div>
                ))}
                <button onClick={addQuizQuestion} className="add-question-btn">
                  <Plus size={14} />
                  Add Question
                </button>
              </div>

              <div className="modal-actions">
                <button onClick={() => setShowQuizModal(false)}>Cancel</button>
                <button className="btn-primary" onClick={handleCreateQuiz}>Create Quiz</button>
              </div>
            </div>
          </div>
        )}

        {/* Confirmation Popup */}
        <ConfirmationPopup
          isOpen={confirmationPopup.isOpen}
          title={confirmationPopup.title}
          message={confirmationPopup.message}
          onConfirm={confirmationPopup.onConfirm}
          onCancel={confirmationPopup.onCancel}
        />
      </div>
    </div>
  );
};

export default AdminContentManagement;
