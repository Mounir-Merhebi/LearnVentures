import React, { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import API from '../../services/axios';
import './AdminDashboard.css';
import { ChevronLeft } from 'lucide-react';

const AdminDailyReports = () => {
  const navigate = useNavigate();
  const [date, setDate] = useState(() => {
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [reports, setReports] = useState([]);
  const [nameQuery, setNameQuery] = useState('');

  const fetchReports = useMemo(() => async (selectedDate, q) => {
    try {
      setLoading(true);
      setError(null);
      const res = await API.get('/reports/daily', { params: { date: selectedDate, student_name: q || undefined } });
      if (res.data && res.data.success) {
        const payload = Array.isArray(res.data.data)
          ? res.data.data
          : (res.data.data?.data || []);
        setReports(payload);
      } else {
        setReports([]);
      }
    } catch (e) {
      setError(e.response?.data?.message || 'Failed to load reports');
      setReports([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    const handler = setTimeout(() => {
      fetchReports(date, nameQuery.trim());
    }, 300);
    return () => clearTimeout(handler);
  }, [date, nameQuery, fetchReports]);

  // navigation back to admin dashboard
  const goDashboard = () => {
    navigate('/admin/dashboard');
  };

  return (
    <div className="admin-dashboard">
      <div className="dashboard-header">
        <h1>Daily Chat Reports</h1>
        <p>Filter by date and review student summaries</p>
        <div className="header-controls">
          <input
            type="text"
            placeholder="Search student by name..."
            value={nameQuery}
            onChange={(e) => setNameQuery(e.target.value)}
            className="search-input"
          />
          <input
            type="date"
            value={date}
            onChange={(e) => setDate(e.target.value)}
            className="date-input"
          />
        </div>
      </div>

      <div className="back-button-container">
        <button className="btn-green" onClick={goDashboard}>
          <ChevronLeft size={16} style={{ marginRight: 6 }} />
          Back to Dashboard
        </button>
      </div>

      <div className="dashboard-content">
        {loading ? (
          <div className="loading">Loading reports...</div>
        ) : error ? (
          <div className="error">{error}</div>
        ) : (
          <div className="reports-table-wrapper">
            {reports.length === 0 ? (
              <div className="empty-state">
                <h3>No reports for {date}</h3>
                <p>Try another date.</p>
              </div>
            ) : (
              <table className="reports-table">
                <thead>
                  <tr>
                    <th>Student</th>
                    <th>Date</th>
                    <th>TL;DR</th>
                    <th>Key Topics</th>
                    <th>Misconceptions</th>
                    <th>Next Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {reports.map((r) => (
                    <tr key={r.id}>
                      <td>{r.user?.name || r.student_name || '—'}</td>
                      <td>{new Date(r.report_date).toLocaleDateString()}</td>
                      <td>{r.tldr || '-'}</td>
                      <td>
                        {Array.isArray(r.key_topics)
                          ? r.key_topics.join(', ')
                          : (typeof r.key_topics === 'string' ? r.key_topics : '-')}
                      </td>
                      <td>
                        {Array.isArray(r.misconceptions)
                          ? r.misconceptions.join(', ')
                          : (typeof r.misconceptions === 'string' ? r.misconceptions : '-')}
                      </td>
                      <td>
                        {Array.isArray(r.next_actions)
                          ? r.next_actions.join(', ')
                          : (typeof r.next_actions === 'string' ? r.next_actions : '-')}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default AdminDailyReports;


