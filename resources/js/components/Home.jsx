import React, { useState, useEffect } from 'react';
import LoadingSpinner from './ui/LoadingSpinner';
import Calendar from './ui/Calendar';
import SummaryPanel from './ui/SummaryPanel';

const Home = () => {
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const loadData = async () => {
      try {
        await new Promise(resolve => setTimeout(resolve, 1000));
        setIsLoading(false);
      } catch (error) {
        console.error('Error loading dashboard data:', error);
        setIsLoading(false);
      }
    };

    loadData();
  }, []);

  if (isLoading) {
    return (
      <div className="min-h-[60vh] flex items-center justify-center">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  return (
    <div className="p-4 md:p-6 space-y-6">
      <SummaryPanel />
      <Calendar />
    </div>
  );
};

export default Home;
