import React from 'react';
import { Outlet } from 'react-router-dom';
import TopNavigation from '../navigation/TopNavigation';
import ChangePasswordModal from '../auth/ChangePasswordModal';
import { useAuth } from '../../context/AuthContext';

function DashboardLayout() {
  const { user } = useAuth();

  return (
    <div className="min-h-screen bg-gray-900">
      <TopNavigation />
      <main className="w-full max-w-[1920px] mx-auto px-4 py-4 md:px-6 mt-16">
        <Outlet />
      </main>
      {user?.must_change_password && <ChangePasswordModal />}
    </div>
  );
}

export default DashboardLayout;
