import { Routes, Route } from "react-router-dom";

import LandingPage from "../pages/LandingPage";
import Auth from "../pages/Auth";
import AIChat from "../pages/AIChat";
import StudentDashboard from "../pages/StudentDashboard";
import Subject from "../pages/Subject";
import Chapter from "../pages/Chapter";
import LessonContent from "../pages/LessonContent";
import Quiz from "../pages/Quiz";
import AdminDashboard from "../pages/AdminDashboard";
import AdminContentManagement from "../pages/AdminDashboard/AdminContentManagement";
import ProtectedRoute from "../components/ProtectedRoute";

const MyRoutes = () => {
  return (
    <Routes>
      <Route path="/" element={<LandingPage />} />
      <Route path="/auth" element={<Auth />} />
      <Route
        path="/optimus"
        element={
          <ProtectedRoute>
            <AIChat />
          </ProtectedRoute>
        }
      />
      <Route
        path="/student_dashboard"
        element={
          <ProtectedRoute>
            <StudentDashboard />
          </ProtectedRoute>
        }
      />
      <Route
        path="/dashboard"
        element={
          <ProtectedRoute>
            <StudentDashboard />
          </ProtectedRoute>
        }
      />
      <Route
        path="/student_dashboard"
        element={
          <ProtectedRoute allowedRoles={["Student"]}>
            <StudentDashboard />
          </ProtectedRoute>
        }
      />
      <Route
        path="/subjects"
        element={
          <ProtectedRoute allowedRoles={["Student", "Instructor", "Moderator"]}>
            <Subject />
          </ProtectedRoute>
        }
      />
      <Route
        path="/subjects/:subjectId"
        element={
          <ProtectedRoute allowedRoles={["Student", "Instructor", "Moderator"]}>
            <Subject />
          </ProtectedRoute>
        }
      />
      <Route
        path="/subjects/:subjectId/chapter/:chapterId"
        element={
          <ProtectedRoute allowedRoles={["Student", "Instructor", "Moderator"]}>
            <Chapter />
          </ProtectedRoute>
        }
      />
      <Route
        path="/subjects/:subjectId/chapter/:chapterId/lesson/:lessonId"
        element={
          <ProtectedRoute allowedRoles={["Student", "Instructor", "Moderator"]}>
            <LessonContent />
          </ProtectedRoute>
        }
      />
      <Route
        path="/subjects/:subjectId/chapter/:chapterId/quiz"
        element={
          <ProtectedRoute allowedRoles={["Student", "Instructor", "Moderator"]}>
            <Quiz />
          </ProtectedRoute>
        }
      />
      <Route
        path="/admin/dashboard"
        element={
          <ProtectedRoute requiredRole="Admin">
            <AdminDashboard />
          </ProtectedRoute>
        }
      />
      <Route
        path="/admin/content"
        element={
          <ProtectedRoute requiredRole="Admin">
            <AdminContentManagement />
          </ProtectedRoute>
        }
      />
    </Routes>
  );
};

export default MyRoutes;
