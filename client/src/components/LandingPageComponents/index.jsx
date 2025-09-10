import React from "react";
import "./styles.css";
import {
  BookOpen,
  Users,
  BarChart3,
  MessagesSquare,
  GraduationCap as GraduationCapLucide,
  CheckCircle as CheckCircleLucide,
} from "lucide-react";

// --- Lucide wrappers matching your old exports --- //
export const BookOpenIcon = ({ className = "icon-teal", size = 48, strokeWidth = 1 }) => (
  <BookOpen className={className} size={size} strokeWidth={strokeWidth} />
);

export const UsersIcon = ({ className = "icon-teal", size = 48, strokeWidth = 1 }) => (
  <Users className={className} size={size} strokeWidth={strokeWidth} />
);

export const ChartBarIcon = ({ className = "icon-teal", size = 48, strokeWidth = 1 }) => (
  <BarChart3 className={className} size={size} strokeWidth={strokeWidth} />
);

export const ChatBubbleLeftRightIcon = ({ className = "icon-teal", size = 48, strokeWidth = 1 }) => (
  <MessagesSquare className={className} size={size} strokeWidth={strokeWidth} />
);

export const GraduationCapIcon = ({ className = "icon-gray", size = 32, strokeWidth = 2 }) => (
  <GraduationCapLucide className={className} size={size} strokeWidth={strokeWidth} />
);

export const CheckCircleIcon = ({ className = "icon-teal", size = 24, strokeWidth = 2 }) => (
  <CheckCircleLucide className={className} size={size} strokeWidth={strokeWidth} />
);

// --- Reusable Components --- //
export const FeatureCard = ({ icon, title, description }) => (
  <div className="feature-card">
    <div className="feature-card-icon">{icon}</div>
    <h3 className="feature-card-title">{title}</h3>
    <p className="feature-card-description">{description}</p>
  </div>
);

export const FeatureListItem = ({ text }) => (
  <li className="student-list-item">
    <CheckCircleIcon />
    <span className="student-list-item-text">{text}</span>
  </li>
);
