import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import './AdminDashboard.css';

const AdminDashboard = () => {
  const navigate = useNavigate();
  const [selectedProposal, setSelectedProposal] = useState(null);
  const [filterStatus, setFilterStatus] = useState('pending');

  // Mock data for change proposals
  const mockProposals = [
    {
      id: 1,
      moderator: {
        id: 5,
        name: 'John Smith',
        email: 'john.smith@example.com'
      },
      created_at: '2024-09-11T10:30:00Z',
      status: 'pending',
      summary: {
        subjects: { create: 2, update: 1, delete: 0 },
        chapters: { create: 0, update: 3, delete: 1 },
        lessons: { create: 1, update: 2, delete: 0 }
      },
      scope: {
        grade_id: 7,
        tables: ['subjects', 'chapters', 'lessons']
      },
      excel_snapshot: {
        subjects: [
          { id: null, title: 'Advanced Mathematics', grade_id: 7, instructor_id: 5, description: 'Advanced math concepts' },
          { id: null, title: 'Physics Fundamentals', grade_id: 7, instructor_id: 5, description: 'Basic physics principles' }
        ],
        chapters: [
          { id: 15, subject_id: 8, title: 'Updated Algebra Chapter', order: 1 }
        ],
        lessons: [
          { id: null, chapter_id: 15, title: 'Linear Equations', content: 'Solving linear equations...', order: 1, version: 1 }
        ]
      },
      db_snapshot: {
        subjects: [],
        chapters: [
          { id: 15, subject_id: 8, title: 'Basic Algebra', order: 1 }
        ],
        lessons: []
      },
      diff_json: {
        subjects: {
          create: [
            { title: 'Advanced Mathematics', grade_id: 7, instructor_id: 5, description: 'Advanced math concepts' },
            { title: 'Physics Fundamentals', grade_id: 7, instructor_id: 5, description: 'Basic physics principles' }
          ],
          update: [],
          delete: []
        },
        chapters: {
          create: [],
          update: [
            { id: 15, title: 'Updated Algebra Chapter' }
          ],
          delete: []
        },
        lessons: {
          create: [
            { chapter_id: 15, title: 'Linear Equations', content: 'Solving linear equations...', order: 1, version: 1 }
          ],
          update: [],
          delete: []
        }
      }
    },
    {
      id: 2,
      moderator: {
        id: 6,
        name: 'Sarah Johnson',
        email: 'sarah.johnson@example.com'
      },
      created_at: '2024-09-11T09:15:00Z',
      status: 'pending',
      summary: {
        subjects: { create: 0, update: 1, delete: 0 },
        chapters: { create: 1, update: 0, delete: 0 },
        lessons: { create: 0, update: 2, delete: 0 }
      },
      scope: {
        grade_id: 8,
        tables: ['subjects', 'chapters', 'lessons']
      },
      excel_snapshot: {
        subjects: [
          { id: 12, title: 'Updated Chemistry', grade_id: 8, instructor_id: 6, description: 'Updated chemistry curriculum' }
        ],
        chapters: [
          { id: null, subject_id: 12, title: 'Organic Chemistry', order: 3 }
        ],
        lessons: [
          { id: 45, chapter_id: 20, title: 'Chemical Reactions Updated', content: 'Updated chemical reactions...', order: 1, version: 2 }
        ]
      },
      db_snapshot: {
        subjects: [
          { id: 12, title: 'Basic Chemistry', grade_id: 8, instructor_id: 6, description: 'Basic chemistry concepts' }
        ],
        chapters: [],
        lessons: [
          { id: 45, chapter_id: 20, title: 'Chemical Reactions', content: 'Basic chemical reactions...', order: 1, version: 1 }
        ]
      },
      diff_json: {
        subjects: {
          create: [],
          update: [
            { id: 12, title: 'Updated Chemistry', description: 'Updated chemistry curriculum' }
          ],
          delete: []
        },
        chapters: {
          create: [
            { subject_id: 12, title: 'Organic Chemistry', order: 3 }
          ],
          update: [],
          delete: []
        },
        lessons: {
          create: [],
          update: [
            { id: 45, title: 'Chemical Reactions Updated', content: 'Updated chemical reactions...', version: 2 }
          ],
          delete: []
        }
      }
    },
    {
      id: 3,
      moderator: {
        id: 7,
        name: 'Mike Davis',
        email: 'mike.davis@example.com'
      },
      created_at: '2024-09-10T14:20:00Z',
      status: 'approved',
      decided_by: {
        id: 1,
        name: 'Admin User'
      },
      summary: {
        subjects: { create: 1, update: 0, delete: 0 },
        chapters: { create: 2, update: 0, delete: 0 },
        lessons: { create: 3, update: 0, delete: 0 }
      },
      scope: {
        grade_id: 9,
        tables: ['subjects', 'chapters', 'lessons']
      },
      excel_snapshot: {
        subjects: [
          { id: null, title: 'Computer Science', grade_id: 9, instructor_id: 7, description: 'Introduction to programming' }
        ],
        chapters: [
          { id: null, subject_id: null, title: 'Programming Basics', order: 1 },
          { id: null, subject_id: null, title: 'Data Structures', order: 2 }
        ],
        lessons: [
          { id: null, chapter_id: null, title: 'Variables and Data Types', content: 'Understanding variables...', order: 1, version: 1 },
          { id: null, chapter_id: null, title: 'Control Structures', content: 'If statements and loops...', order: 2, version: 1 },
          { id: null, chapter_id: null, title: 'Functions', content: 'Creating and using functions...', order: 3, version: 1 }
        ]
      },
      db_snapshot: {
        subjects: [],
        chapters: [],
        lessons: []
      },
      diff_json: {
        subjects: {
          create: [
            { title: 'Computer Science', grade_id: 9, instructor_id: 7, description: 'Introduction to programming' }
          ],
          update: [],
          delete: []
        },
        chapters: {
          create: [
            { subject_id: null, title: 'Programming Basics', order: 1 },
            { subject_id: null, title: 'Data Structures', order: 2 }
          ],
          update: [],
          delete: []
        },
        lessons: {
          create: [
            { chapter_id: null, title: 'Variables and Data Types', content: 'Understanding variables...', order: 1, version: 1 },
            { chapter_id: null, title: 'Control Structures', content: 'If statements and loops...', order: 2, version: 1 },
            { chapter_id: null, title: 'Functions', content: 'Creating and using functions...', order: 3, version: 1 }
          ],
          update: [],
          delete: []
        }
      }
    }
  ];

  const filteredProposals = mockProposals.filter(proposal =>
    filterStatus === 'all' || proposal.status === filterStatus
  );

  const handleApprove = (proposalId) => {
    console.log('Approving proposal:', proposalId);
    // TODO: Implement approval logic
  };

  const handleReject = (proposalId) => {
    console.log('Rejecting proposal:', proposalId);
    // TODO: Implement rejection logic
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'pending': return '#ffc107';
      case 'approved': return '#28a745';
      case 'rejected': return '#dc3545';
      case 'applied': return '#17a2b8';
      case 'failed': return '#6c757d';
      default: return '#6c757d';
    }
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  return (
    <div className="admin-dashboard">
      <div className="dashboard-header">
        <div>
          <h1>Admin Dashboard</h1>
          <p>Manage content and monitor the platform</p>
        </div>
        <div>
          <button
            className="nav-btn"
            onClick={() => {
              // Clear auth and redirect to login
              localStorage.removeItem('token');
              localStorage.removeItem('user');
              navigate('/auth');
            }}
          >
            Logout
          </button>
        </div>
      </div>

      <div className="dashboard-content">
        <div className="filters-section" style={{ display: 'none' }}>
          <div className="filter-buttons">
            <button
              className={`filter-btn ${filterStatus === 'all' ? 'active' : ''}`}
              onClick={() => setFilterStatus('all')}
            >
              All ({mockProposals.length})
            </button>
            <button
              className={`filter-btn ${filterStatus === 'pending' ? 'active' : ''}`}
              onClick={() => setFilterStatus('pending')}
            >
              Pending ({mockProposals.filter(p => p.status === 'pending').length})
            </button>
            <button
              className={`filter-btn ${filterStatus === 'approved' ? 'active' : ''}`}
              onClick={() => setFilterStatus('approved')}
            >
              Approved ({mockProposals.filter(p => p.status === 'approved').length})
            </button>
            <button
              className={`filter-btn ${filterStatus === 'rejected' ? 'active' : ''}`}
              onClick={() => setFilterStatus('rejected')}
            >
              Rejected ({mockProposals.filter(p => p.status === 'rejected').length})
            </button>
          </div>
        </div>

        <div className="proposals-grid" style={{ display: 'none' }}>
          {filteredProposals.map(proposal => (
            <div key={proposal.id} className="proposal-card">
              <div className="proposal-header">
                <div className="proposal-info">
                  <h3>Proposal #{proposal.id}</h3>
                  <div className="moderator-info">
                    <span>By: {proposal.moderator.name}</span>
                    <span>•</span>
                    <span>{formatDate(proposal.created_at)}</span>
                  </div>
                </div>
                <div
                  className="status-badge"
                  style={{ backgroundColor: getStatusColor(proposal.status) }}
                >
                  {proposal.status.charAt(0).toUpperCase() + proposal.status.slice(1)}
                </div>
              </div>

              <div className="proposal-scope">
                <span className="scope-label">Scope:</span>
                <span>Grade {proposal.scope.grade_id}</span>
                <span>•</span>
                <span>{proposal.scope.tables.join(', ')}</span>
              </div>

              <div className="proposal-summary">
                <h4>Changes Summary:</h4>
                <div className="summary-grid">
                  {Object.entries(proposal.summary).map(([table, counts]) => (
                    <div key={table} className="table-summary">
                      <span className="table-name">{table}:</span>
                      <div className="counts">
                        {counts.create > 0 && <span className="count create">+{counts.create}</span>}
                        {counts.update > 0 && <span className="count update">~{counts.update}</span>}
                        {counts.delete > 0 && <span className="count delete">-{counts.delete}</span>}
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              <div className="proposal-actions">
                {proposal.status === 'pending' ? (
                  <>
                    <button
                      className="action-btn approve-btn"
                      onClick={() => handleApprove(proposal.id)}
                    >
                      ✓ Approve
                    </button>
                    <button
                      className="action-btn reject-btn"
                      onClick={() => handleReject(proposal.id)}
                    >
                      ✗ Reject
                    </button>
                  </>
                ) : (
                  <div className="decision-info">
                    {proposal.decided_by && (
                      <span>Decided by: {proposal.decided_by.name}</span>
                    )}
                  </div>
                )}
                <button
                  className="action-btn view-btn"
                  onClick={() => setSelectedProposal(proposal)}
                >
                  View Details
                </button>
              </div>
            </div>
          ))}
        </div>

        {false && selectedProposal && (
          <div className="proposal-detail-modal">
            <div className="modal-content">
              <div className="modal-header">
                <h2>Proposal #{selectedProposal.id} Details</h2>
                <button
                  className="close-btn"
                  onClick={() => setSelectedProposal(null)}
                >
                  ×
                </button>
              </div>

              <div className="modal-body">
                <div className="detail-section">
                  <h3>Basic Information</h3>
                  <div className="info-grid">
                    <div><strong>Moderator:</strong> {selectedProposal.moderator.name}</div>
                    <div><strong>Email:</strong> {selectedProposal.moderator.email}</div>
                    <div><strong>Created:</strong> {formatDate(selectedProposal.created_at)}</div>
                    <div><strong>Status:</strong>
                      <span
                        className="status-badge"
                        style={{ backgroundColor: getStatusColor(selectedProposal.status) }}
                      >
                        {selectedProposal.status}
                      </span>
                    </div>
                  </div>
                </div>

                <div className="detail-section">
                  <h3>Scope</h3>
                  <div className="scope-details">
                    <div><strong>Grade ID:</strong> {selectedProposal.scope.grade_id}</div>
                    <div><strong>Tables:</strong> {selectedProposal.scope.tables.join(', ')}</div>
                  </div>
                </div>

                <div className="detail-section">
                  <h3>Changes Overview</h3>
                  <div className="changes-overview">
                    {Object.entries(selectedProposal.diff_json).map(([table, operations]) => (
                      <div key={table} className="table-changes">
                        <h4>{table.charAt(0).toUpperCase() + table.slice(1)}</h4>
                        {operations.create && operations.create.length > 0 && (
                          <div className="operation-section">
                            <h5>Create ({operations.create.length})</h5>
                            <pre className="json-preview">
                              {JSON.stringify(operations.create, null, 2)}
                            </pre>
                          </div>
                        )}
                        {operations.update && operations.update.length > 0 && (
                          <div className="operation-section">
                            <h5>Update ({operations.update.length})</h5>
                            <pre className="json-preview">
                              {JSON.stringify(operations.update, null, 2)}
                            </pre>
                          </div>
                        )}
                        {operations.delete && operations.delete.length > 0 && (
                          <div className="operation-section">
                            <h5>Delete ({operations.delete.length})</h5>
                            <pre className="json-preview">
                              {JSON.stringify(operations.delete, null, 2)}
                            </pre>
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                </div>

                {selectedProposal.status === 'pending' && (
                  <div className="modal-actions">
                    <button
                      className="action-btn approve-btn"
                      onClick={() => {
                        handleApprove(selectedProposal.id);
                        setSelectedProposal(null);
                      }}
                    >
                      ✓ Approve Changes
                    </button>
                    <button
                      className="action-btn reject-btn"
                      onClick={() => {
                        handleReject(selectedProposal.id);
                        setSelectedProposal(null);
                      }}
                    >
                      ✗ Reject Changes
                    </button>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default AdminDashboard;
