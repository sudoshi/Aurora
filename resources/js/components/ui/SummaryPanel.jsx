import React, { useState } from 'react';
import { 
  Users, 
  Clock, 
  AlertTriangle, 
  Microscope,
  Bell
} from 'lucide-react';
import SummaryModal from './SummaryModal';

const MetricCard = ({ 
  title, 
  value, 
  status, 
  icon: Icon, 
  color, 
  onClick, 
  alerts = []
}) => (
  <div className="flex flex-col">
    <button 
      onClick={onClick}
      className={`
        flex items-start gap-4 p-4 rounded-lg
        bg-gray-800 border border-gray-700
        hover:bg-gray-700/50 transition-colors
        focus:outline-none focus:ring-2 focus:ring-${color}-500
        ${alerts.length > 0 ? `ring-1 ring-${color}-500` : ''}
      `}
    >
      <div className={`
        p-3 rounded-lg shrink-0
        bg-${color}-900/30
        text-${color}-400
        relative
      `}>
        <Icon className="w-6 h-6" />
        {alerts.length > 0 && (
          <span className="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full" />
        )}
      </div>
      <div className="flex-1 min-w-0 text-left">
        <h3 className="text-sm font-medium text-gray-400">{title}</h3>
        <p className="mt-1 text-2xl font-semibold text-white">{value}</p>
        {status && (
          <p className={`mt-1 text-sm text-${color}-400 truncate`}>
            {status}
          </p>
        )}
      </div>
    </button>
  </div>
);

const SummaryPanel = () => {
  const [selectedMetric, setSelectedMetric] = useState(null);

  // Enhanced mock data with alerts
  const metrics = [
    {
      id: 'critical',
      title: 'Critical Cases',
      value: '5',
      status: '2 Immediate Action Required',
      icon: AlertTriangle,
      color: 'red',
      alerts: [
        {
          title: 'Post-Transplant Patient: Elevated Liver Enzymes',
          time: '10 minutes ago',
          color: 'red'
        },
        {
          title: 'ICU Patient: Deteriorating Vital Signs',
          time: '15 minutes ago',
          color: 'red'
        }
      ]
    },
    {
      id: 'pending-consults',
      title: 'Pending Consults',
      value: '12',
      status: '4 High Priority',
      icon: Clock,
      color: 'orange',
      alerts: [
        {
          title: 'Cardiology Consult Requested',
          time: '30 minutes ago',
          color: 'orange'
        },
        {
          title: 'Neurology Assessment Needed',
          time: '1 hour ago',
          color: 'orange'
        }
      ]
    },
    {
      id: 'tumor-board',
      title: 'Tumor Board Cases',
      value: '8',
      status: '3 New Cases Added',
      icon: Microscope,
      color: 'purple',
      alerts: [
        {
          title: 'New Pathology Results Available',
          time: '2 hours ago',
          color: 'purple'
        }
      ]
    },
    {
      id: 'team-updates',
      title: 'Care Team Updates',
      value: '15',
      status: '6 Unread Messages',
      icon: Users,
      color: 'blue',
      alerts: [
        {
          title: 'Treatment Plan Modified',
          time: '45 minutes ago',
          color: 'blue'
        },
        {
          title: 'New Lab Results Discussion',
          time: '1.5 hours ago',
          color: 'blue'
        }
      ]
    }
  ];

  return (
    <>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {metrics.map((metric) => (
          <MetricCard 
            key={metric.id}
            {...metric}
            onClick={() => setSelectedMetric(metric)}
          />
        ))}
      </div>
      {selectedMetric && (
        <SummaryModal 
          metric={selectedMetric} 
          onClose={() => setSelectedMetric(null)} 
        />
      )}
    </>
  );
};

export default SummaryPanel;
