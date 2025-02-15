import React from 'react';
import { createPortal } from 'react-dom';
import { X, Users, Clock, AlertTriangle, Microscope, Bell, ChevronRight, FileText, MessageSquare } from 'lucide-react';

const SummaryModal = ({ metric, onClose }) => {
  if (!metric) return null;

  const getDetailedContent = () => {
    switch (metric.id) {
      case 'critical':
        return {
          stats: [
            { label: 'Immediate Action', value: '2 cases' },
            { label: 'Under Observation', value: '3 cases' }
          ],
          sections: [
            {
              title: 'Critical Patients',
              items: metric.alerts.map(alert => ({
                icon: AlertTriangle,
                iconColor: 'red',
                title: alert.title,
                description: alert.time,
                type: 'alert'
              }))
            }
          ]
        };
      case 'pending-consults':
        return {
          stats: [
            { label: 'High Priority', value: '4 consults' },
            { label: 'Standard Priority', value: '8 consults' }
          ],
          sections: [
            {
              title: 'Pending Consultations',
              items: metric.alerts.map(alert => ({
                icon: Clock,
                iconColor: 'orange',
                title: alert.title,
                description: alert.time,
                type: 'consult'
              }))
            }
          ]
        };
      case 'tumor-board':
        return {
          stats: [
            { label: 'New Cases', value: '3 cases' },
            { label: 'Follow-ups', value: '5 cases' }
          ],
          sections: [
            {
              title: 'Case Reviews',
              items: metric.alerts.map(alert => ({
                icon: Microscope,
                iconColor: 'purple',
                title: alert.title,
                description: alert.time,
                type: 'case'
              }))
            }
          ]
        };
      case 'team-updates':
        return {
          stats: [
            { label: 'Unread Messages', value: '6 messages' },
            { label: 'Team Members', value: '15 active' }
          ],
          sections: [
            {
              title: 'Recent Updates',
              items: metric.alerts.map(alert => ({
                icon: MessageSquare,
                iconColor: 'blue',
                title: alert.title,
                description: alert.time,
                type: 'update'
              }))
            }
          ]
        };
      default:
        return { stats: [], sections: [] };
    }
  };

  const content = getDetailedContent();
  const Icon = metric.icon;

  return createPortal(
    <div className="fixed inset-0 z-[100] overflow-y-auto">
      <div className="fixed inset-0 bg-black/70 backdrop-blur-sm" onClick={onClose} />
      
      <div className="relative min-h-screen flex items-center justify-center p-4">
        <div className="relative bg-gray-900 rounded-xl shadow-xl w-full max-w-3xl">
          {/* Header */}
          <div className="flex items-start justify-between p-6 border-b border-gray-700">
            <div className="flex items-center gap-4">
              <div className={`p-3 rounded-lg bg-${metric.color}-900/30 text-${metric.color}-400`}>
                <Icon className="w-6 h-6" />
              </div>
              <div>
                <h2 className="text-xl font-semibold text-white">{metric.title}</h2>
                <p className={`mt-1 text-sm text-${metric.color}-400`}>{metric.status}</p>
              </div>
            </div>
            <button 
              onClick={onClose}
              className="p-2 hover:bg-gray-800 rounded-lg transition-colors"
            >
              <X className="w-5 h-5 text-gray-400" />
            </button>
          </div>

          {/* Content */}
          <div className="p-6 space-y-6">
            {/* Key Stats */}
            <div className="grid grid-cols-2 gap-4">
              {content.stats.map((stat, index) => (
                <div key={index} className="flex items-center gap-3 p-3 bg-gray-800/50 rounded-lg">
                  <div className="p-2 bg-gray-800 rounded-lg">
                    <Bell className={`w-5 h-5 text-${metric.color}-400`} />
                  </div>
                  <div>
                    <p className="text-sm text-gray-400">{stat.label}</p>
                    <p className="text-sm font-medium text-white">{stat.value}</p>
                  </div>
                </div>
              ))}
            </div>

            {/* Sections */}
            {content.sections.map((section, sectionIndex) => (
              <div key={sectionIndex}>
                <div className="flex items-center justify-between mb-2">
                  <h3 className="text-sm font-medium text-gray-400">{section.title}</h3>
                  <span className="text-xs text-gray-500">{section.items.length} items</span>
                </div>
                <div className="space-y-2">
                  {section.items.map((item, itemIndex) => (
                    <button 
                      key={itemIndex}
                      className="w-full flex items-center justify-between p-3 bg-gray-800/50 rounded-lg hover:bg-gray-800 transition-colors"
                    >
                      <div className="flex items-center gap-3">
                        <div className="p-2 bg-gray-800 rounded-lg">
                          <item.icon className={`w-4 h-4 text-${item.iconColor}-400`} />
                        </div>
                        <div className="text-left">
                          <p className="text-sm font-medium text-white">{item.title}</p>
                          <p className="text-xs text-gray-400">{item.description}</p>
                        </div>
                      </div>
                      <ChevronRight className="w-4 h-4 text-gray-400" />
                    </button>
                  ))}
                </div>
              </div>
            ))}
          </div>

          {/* Footer */}
          <div className="flex items-center justify-end gap-3 p-6 border-t border-gray-700">
            <button 
              onClick={onClose}
              className="px-4 py-2 text-sm font-medium text-gray-400 hover:text-white"
            >
              Close
            </button>
            <button className={`px-4 py-2 bg-${metric.color}-600 text-white text-sm font-medium rounded-lg hover:bg-${metric.color}-700`}>
              View All
            </button>
          </div>
        </div>
      </div>
    </div>
    , document.body
  );
};

export default SummaryModal;
