import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ChevronLeft, Calendar, Users } from 'lucide-react';
import CaseDiscussion from './CaseDiscussion';
import ErrorBoundary from './ui/ErrorBoundary';
import { SuperNoteFollowUp } from './SuperNoteFollowUp';
import CaseOverview from './CaseOverview';
import ImagingView from './ImagingView';
import LabsView from './LabsView';
import MolecularView from './MolecularView';
import PrognosisView from './PrognosisView';

const Collaboration = () => {
  const { eventId } = useParams();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('overview');
  const [selectedPatient, setSelectedPatient] = useState(null);
  const [eventData, setEventData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const fetchEventData = async () => {
      setIsLoading(true); // Set loading state at the beginning
      try {
        console.log(`Fetching event data for eventId: ${eventId}`);
        const response = await fetch(`/api/events/${eventId}`);

        if (!response.ok) {
          const message = `Failed to fetch event data. Status: ${response.status} ${response.statusText}`;
          const error = new Error(message);
          console.error(error);
          throw error;
        }

        const data = await response.json();
        console.log('Raw API response:', data);

        try {
          // Parse the JSON patients data from the event
          const patientsData = typeof data.patients === 'string' ? JSON.parse(data.patients) : data.patients;
          console.log('Patients data:', patientsData);

          const transformedData = {
            ...data,
            patients: patientsData.map(patient => {
              console.log('Mapping patient:', patient);
              return {
                id: patient.id,
                firstName: patient.name.split(' ')[0],
                lastName: patient.name.split(' ')[1] || '',
                mrn: `MRN${String(patient.id).padStart(6, '0')}`,
                primaryDiagnosis: patient.condition,
                careJourney: patient.status,
                teamMembers: data.team_members?.map(member => `${member.name} (${member.role})`) || [],
                nextReviewDate: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                description: patient.condition,
                upcomingSessions: [
                  {
                    id: patient.id,
                    startTime: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString(),
                    participants: data.team_members?.map(member => member.name) || []
                  }
                ]
              };
            })
          };

          console.log('Transformed data:', transformedData);
          setEventData(transformedData);
          if (transformedData.patients && transformedData.patients.length > 0) {
            setSelectedPatient(transformedData.patients[0]);
          }
        } catch (transformError) {
          console.error('Error transforming data:', transformError);
          throw transformError; // Re-throw to be caught by outer try-catch
        }
      } catch (error) {
        console.error('Error fetching or transforming event data:', error);
        // Additional error handling or display to the user
      } finally {
        setIsLoading(false); // Ensure loading state is reset
      }
    };

    fetchEventData();
  }, [eventId]);

  const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'supernote', label: 'SuperNote' },
    { id: 'imaging', label: 'Imaging' },
    { id: 'labs', label: 'Labs' },
    { id: 'molecular', label: 'Molecular' },
    { id: 'prognosis', label: 'Prognosis' },
    { id: 'discussion', label: 'Discussion' }
  ];

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gray-900 flex items-center justify-center">
        <div className="bg-gray-800 p-4 rounded shadow">
          <p className="text-gray-400">Loading...</p>
        </div>
      </div>
    );
  }

  if (!selectedPatient) {
    return (
      <div className="min-h-screen bg-gray-900 flex items-center justify-center">
        <div className="bg-gray-800 p-4 rounded shadow">
          <p className="text-gray-400">No patient selected</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-900">
      {/* Header */}
      <div className="bg-gray-800 border-b border-gray-700 rounded-t-lg">
        <div className="w-full px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center">
            <button
              onClick={() => navigate(-1)}
              className="mr-4 p-2 hover:bg-gray-700 rounded-lg transition-colors"
            >
              <ChevronLeft className="w-5 h-5 text-gray-400" />
            </button>
            <div>
              <h1 className="text-2xl font-semibold text-gray-100">MDC Evaluation</h1>
              <p className="text-sm text-gray-400">Event ID: {eventId}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-700 bg-gray-800 rounded-b-lg">
        <div className="w-full px-4 sm:px-6 lg:px-8">
          <nav className="-mb-px flex space-x-4">
            {tabs.map(tab => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`whitespace-nowrap py-2 px-4 font-medium text-sm rounded-t-lg transition-colors
                  ${activeTab === tab.id
                    ? 'bg-blue-900/50 text-blue-400 border-b-2 border-blue-500'
                    : 'text-gray-400 hover:text-gray-300 hover:bg-gray-700/50'
                  }`}
              >
                {tab.label}
              </button>
            ))}
          </nav>
        </div>
      </div>

      {/* Main Content Area */}
      <div className="w-full h-[calc(100vh-116px)] px-4 sm:px-6 lg:px-8 py-8 flex gap-6">
        {/* Patient List Sidebar */}
        <div className="w-80 min-w-[320px] max-w-md bg-gray-800 rounded-lg border border-gray-700 shadow-lg overflow-y-auto">
          <div className="p-4 border-b border-gray-700">
            <h2 className="text-lg font-semibold text-gray-100">Patients</h2>
          </div>
          <div className="p-2">
            {eventData?.patients.map((patient) => (
              <button
                key={patient.id}
                onClick={() => setSelectedPatient(patient)}
                className={`w-full text-left p-4 rounded-lg mb-2 transition-colors ${selectedPatient?.id === patient.id
                    ? 'bg-blue-900/50 border border-blue-500'
                    : 'bg-gray-700/50 border border-gray-600 hover:bg-gray-700'
                  }`}
              >
                <div className="flex justify-between items-start mb-2">
                  <div>
                    <h3 className="text-gray-100 font-medium">{patient.lastName}, {patient.firstName}</h3>
                    <p className="text-sm text-gray-400">MRN: {patient.mrn}</p>
                  </div>
                </div>
                <p className="text-sm text-gray-300 font-medium mb-1">{patient.primaryDiagnosis}</p>
                <p className="text-sm text-gray-400 mb-2">{patient.careJourney}</p>
                <div className="flex items-center text-sm text-gray-400 gap-4">
                  <span className="flex items-center gap-1">
                    <Users className="w-3 h-3" />
                    {patient.teamMembers.length}
                  </span>
                  <span className="flex items-center gap-1">
                    <Calendar className="w-3 h-3" />
                    {new Date(patient.nextReviewDate).toLocaleDateString()}
                  </span>
                </div>
              </button>
            ))}
          </div>
        </div>

        {/* Main Content */}
        <div className="flex-1 min-w-0">
          {activeTab === 'overview' && (
            <div className="bg-gray-800 rounded-lg border border-gray-700 shadow-lg p-6">
              <CaseOverview
                caseData={{
                  patient: selectedPatient,
                  description: selectedPatient.description,
                  nextReviewDate: selectedPatient.nextReviewDate,
                  teamMembers: selectedPatient.teamMembers,
                  upcomingSessions: selectedPatient.upcomingSessions,
                }}
              />
            </div>
          )}

          {activeTab === 'supernote' && (
            <div className="bg-gray-800 rounded-lg border border-gray-700 shadow-lg overflow-hidden">
              <ErrorBoundary>
                <SuperNoteFollowUp
                  note={{
                    patientId: selectedPatient.id,
                    followUpDetails: { /* ... supernote details */ }
                  }}
                  isRecording={false}
                  onStartRecording={() => { }}
                  onStopRecording={() => { }}
                  onSave={() => { }}
                  onNoteChange={() => { }}
                />
              </ErrorBoundary>
            </div>
          )}

          {activeTab === 'imaging' && (
            <div className="bg-gray-800 rounded-lg border border-gray-700 shadow-lg overflow-hidden">
              <ErrorBoundary>
                <div className="p-6">
                  <ImagingView patientId={selectedPatient.id} />
                </div>
              </ErrorBoundary>
            </div>
          )}

          {activeTab === 'labs' && (
            <div className="bg-gray-800 rounded-lg border border-gray-700 shadow-lg overflow-hidden">
              <ErrorBoundary>
                <div className="p-6">
                  <LabsView patientId={selectedPatient.id} />
                </div>
              </ErrorBoundary>
            </div>
          )}

          {activeTab === 'molecular' && (
            <div className="bg-gray-800 rounded-lg border border-gray-700 shadow-lg overflow-hidden">
              <ErrorBoundary>
                <div className="p-6">
                  <MolecularView patientId={selectedPatient.id} />
                </div>
              </ErrorBoundary>
            </div>
          )}

          {activeTab === 'prognosis' && (
            <div className="bg-gray-800 rounded-lg border border-gray-700 shadow-lg overflow-hidden">
              <ErrorBoundary>
                <div className="p-6">
                  <PrognosisView patientId={selectedPatient.id} />
                </div>
              </ErrorBoundary>
            </div>
          )}

          {activeTab === 'discussion' && (
            <div className="bg-gray-800 rounded-lg border border-gray-700 shadow-lg overflow-hidden">
              <div className="h-[calc(100vh-180px)]">
                <ErrorBoundary
                  fallback={
                    <div className="p-4 text-center">
                      <p className="text-red-200">Something went wrong with the discussion component.</p>
                      <button onClick={() => window.location.reload()} className="mt-2 px-4 py-2 bg-red-900 text-red-100 rounded hover:bg-red-800">
                        Reload Page
                      </button>
                    </div>
                  }
                >
                  <CaseDiscussion caseId={eventId} />
                </ErrorBoundary>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default Collaboration;
