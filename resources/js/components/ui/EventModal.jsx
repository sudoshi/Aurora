import React from 'react';
import { X, Users, Clock, MapPin, FileText, AlertTriangle, MessageSquare, ChevronRight, Activity, Heart, Microscope, Brain, Stethoscope } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

const EventModal = ({ event, onClose }) => {
  const navigate = useNavigate();
  if (!event) return null;
  
  console.log('EventModal opened with event:', event);
  console.log('Event patients:', event.patients);
  console.log('Event patients length:', event.patients?.length);

  const handleUpdateStatus = () => {
    // TODO: Implement status update functionality
    console.log('Update status clicked for event:', event.id);
  };

  const categoryColors = {
    'icu': 'red',
    'surgical': 'purple',
    'oncology': 'blue',
    'neurology': 'teal',
    'admin': 'gray',
    'multidisciplinary': 'green'
  };

  const categoryIcons = {
    'icu': Activity,
    'surgical': Heart,
    'oncology': Microscope,
    'neurology': Brain,
    'admin': Users,
    'multidisciplinary': Stethoscope
  };

  const categoryLabels = {
    'icu': 'ICU/Critical Care',
    'surgical': 'Surgical/Transplant',
    'oncology': 'Oncology',
    'neurology': 'Neurology',
    'admin': 'Administrative',
    'multidisciplinary': 'Multi-disciplinary'
  };

  const Icon = categoryIcons[event.category];
  const color = categoryColors[event.category];

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="fixed inset-0 bg-black/70 backdrop-blur-sm" onClick={onClose} />
      
      <div className="relative min-h-screen flex items-center justify-center p-4">
        <div className="relative bg-gray-900 rounded-xl shadow-xl w-full max-w-3xl">
          {/* Header */}
          <div className="flex items-start justify-between p-6 border-b border-gray-700">
            <div className="flex items-center justify-between w-full">
              <div>
                <div className="flex items-center gap-3 mb-2">
                  <div className={`px-3 py-1 rounded-full text-sm font-medium bg-${color}-900/30 text-${color}-400 flex items-center gap-2`}>
                    <Icon className="w-4 h-4" />
                    <span>{categoryLabels[event.category]}</span>
                  </div>
                  <span className="text-sm text-gray-400">{event.time}</span>
                </div>
                <h2 className="text-xl font-semibold text-white">{event.title}</h2>
              </div>
              <button
                onClick={onClose}
                className="rounded-full p-2 hover:bg-gray-800 transition-colors"
              >
                <X className="w-5 h-5 text-gray-400" />
              </button>
            </div>
          </div>

          {/* Content */}
          <div className="p-6 space-y-6">
            {/* Key Details */}
            <div className="grid grid-cols-2 gap-4">
              <div className="flex items-center gap-3 p-3 bg-gray-800/50 rounded-lg">
                <div className="p-2 bg-gray-800 rounded-lg">
                  <Clock className="w-5 h-5 text-gray-400" />
                </div>
                <div>
                  <p className="text-sm text-gray-400">Duration</p>
                  <p className="text-sm font-medium text-white">{event.duration} minutes</p>
                </div>
              </div>
              <div className="flex items-center gap-3 p-3 bg-gray-800/50 rounded-lg">
                <div className="p-2 bg-gray-800 rounded-lg">
                  <MapPin className="w-5 h-5 text-gray-400" />
                </div>
                <div>
                  <p className="text-sm text-gray-400">Location</p>
                  <p className="text-sm font-medium text-white">{event.location}</p>
                </div>
              </div>
            </div>

            {/* Description */}
            {event.description && (
              <div>
                <h3 className="text-sm font-medium text-gray-400 mb-2">Description</h3>
                <p className="text-sm text-white">{event.description}</p>
              </div>
            )}

            {/* Team Members */}
            {event.team && (
              <div>
                <div className="flex items-center justify-between mb-2">
                  <h3 className="text-sm font-medium text-gray-400">Care Team</h3>
                  <span className="text-xs text-gray-500">{event.team.length} members</span>
                </div>
                <div className="space-y-2">
                  {event.team.map((member, index) => (
                    <div 
                      key={index}
                      className="flex items-center justify-between p-2 bg-gray-800/50 rounded-lg"
                    >
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center">
                          <span className="text-sm font-medium text-white">
                            {member.name.split(' ').map(n => n[0]).join('')}
                          </span>
                        </div>
                        <div>
                          <p className="text-sm font-medium text-white">{member.name}</p>
                          <p className="text-xs text-gray-400">{member.role}</p>
                        </div>
                      </div>
                      <div className="flex items-center gap-2">
                        <div className={`w-2 h-2 rounded-full ${member.available ? 'bg-green-500' : 'bg-gray-500'}`} />
                        <span className="text-xs text-gray-400">
                          {member.available ? 'Available' : 'Unavailable'}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Patients */}
            {event.patients && event.patients.length > 0 && (
              <div>
                <div className="flex items-center justify-between mb-2">
                  <h3 className="text-sm font-medium text-gray-400">Patients</h3>
                  <span className="text-xs text-gray-500">{event.patients.length} patients</span>
                </div>
                <div className="space-y-2">
                  {event.patients.map((patient, index) => (
                    <div 
                      key={index}
                      className="p-3 bg-gray-800/50 rounded-lg space-y-2"
                    >
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                          <div className="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center">
                            <span className="text-sm font-medium text-white">
                              {patient.name.split(' ').map(n => n[0]).join('')}
                            </span>
                          </div>
                          <div>
                            <p className="text-sm font-medium text-white">{patient.name}</p>
                            <p className="text-xs text-gray-400">ID: {patient.id}</p>
                          </div>
                        </div>
                      </div>
                      <div className="pl-11">
                        <div className="space-y-1">
                          <p className="text-sm text-white">{patient.condition}</p>
                          <p className="text-sm text-gray-400">{patient.status}</p>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Related Items */}
            {event.relatedItems && (
              <div>
                <h3 className="text-sm font-medium text-gray-400 mb-2">Related Items</h3>
                <div className="space-y-2">
                  {event.relatedItems.map((item, index) => (
                    <button 
                      key={index}
                      className="w-full flex items-center justify-between p-3 bg-gray-800/50 rounded-lg hover:bg-gray-800 transition-colors"
                    >
                      <div className="flex items-center gap-3">
                        <div className="p-2 bg-gray-800 rounded-lg">
                          {item.type === 'document' && <FileText className="w-4 h-4 text-blue-400" />}
                          {item.type === 'alert' && <AlertTriangle className="w-4 h-4 text-red-400" />}
                          {item.type === 'note' && <MessageSquare className="w-4 h-4 text-green-400" />}
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
            )}
          </div>

          {/* Footer */}
          <div className="flex items-center justify-end gap-3 p-6 border-t border-gray-700">
            <button 
              onClick={onClose}
              className="px-4 py-2 text-sm font-medium text-gray-400 hover:text-white bg-gray-800 rounded-lg"
            >
              Cancel
            </button>
            <button
              onClick={() => {
                onClose();
                navigate(`/collaboration/${event.id}`);
              }}
              className="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 flex items-center gap-2"
            >
              <Users className="w-4 h-4" />
              See Patients
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default EventModal;
