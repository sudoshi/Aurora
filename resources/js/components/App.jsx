import React, { Suspense } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from '../context/AuthContext.jsx';
import DashboardLayout from './layouts/DashboardLayout.jsx';
import PrivateRoute from './PrivateRoute.jsx';
import Login from './Login.jsx';

// Lazy load components
const Home = React.lazy(() => import('./Home.jsx'));
const About = React.lazy(() => import('./About.jsx'));

// Placeholder component for routes under development
const UnderDevelopment = () => (
  <div className="flex flex-col items-center justify-center min-h-[60vh] text-gray-300">
    <div className="bg-gray-800 p-8 rounded-lg shadow-lg text-center">
      <h2 className="text-2xl font-bold mb-4">Under Development</h2>
      <p className="text-gray-400">This feature is currently being developed.</p>
      <p className="text-gray-400 mt-2">Please check back soon!</p>
    </div>
  </div>
);

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Suspense fallback={
          <div className="flex items-center justify-center min-h-screen bg-gray-900">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
          </div>
        }>
          <Routes>
            {/* Public Routes */}
            <Route path="/login" element={<Login />} />

            {/* Protected Routes */}
            <Route element={<PrivateRoute><DashboardLayout /></PrivateRoute>}>
              <Route path="/" element={<Home />} />
              <Route path="/about" element={<About />} />
              
              {/* Collaboration Routes */}
              <Route path="/video-conferences" element={<UnderDevelopment />} />
              <Route path="/screen-sharing" element={<UnderDevelopment />} />
              <Route path="/whiteboard" element={<UnderDevelopment />} />
              <Route path="/active-sessions" element={<UnderDevelopment />} />
              <Route path="/join-meeting" element={<UnderDevelopment />} />

              {/* Communication Routes */}
              <Route path="/case-discussions" element={<UnderDevelopment />} />
              <Route path="/tasks" element={<UnderDevelopment />} />
              <Route path="/files" element={<UnderDevelopment />} />
              <Route path="/notifications" element={<UnderDevelopment />} />
              <Route path="/messages" element={<UnderDevelopment />} />

              {/* Clinical Routes */}
              <Route path="/decision-support" element={<UnderDevelopment />} />
              <Route path="/lab-results" element={<UnderDevelopment />} />
              <Route path="/medications" element={<UnderDevelopment />} />
              <Route path="/risk-assessment" element={<UnderDevelopment />} />
              <Route path="/guidelines" element={<UnderDevelopment />} />

              {/* Team Routes */}
              <Route path="/schedule" element={<UnderDevelopment />} />
              <Route path="/availability" element={<UnderDevelopment />} />
              <Route path="/documents" element={<UnderDevelopment />} />
              <Route path="/audit-logs" element={<UnderDevelopment />} />
              <Route path="/resources" element={<UnderDevelopment />} />

              {/* User Routes */}
              <Route path="/profile" element={<UnderDevelopment />} />
              <Route path="/sessions" element={<UnderDevelopment />} />
              <Route path="/security-log" element={<UnderDevelopment />} />
              <Route path="/support" element={<UnderDevelopment />} />
            </Route>

            {/* Catch all route - redirect to home */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </Suspense>
      </BrowserRouter>
    </AuthProvider>
  );
}

export default App;
