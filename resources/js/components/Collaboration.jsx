import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ChevronLeft, Calendar, Users, Clock } from 'lucide-react';
import CaseDiscussion from './CaseDiscussion';
import ErrorBoundary from './ui/ErrorBoundary';
import { SuperNoteFollowUp } from './SuperNoteFollowUp';
import CaseOverview from './CaseOverview';

const Collaboration = () => {
  const { eventId } = useParams();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('overview');
  const [selectedPatient, setSelectedPatient] = useState(null);
  const [eventData, setEventData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  // Mock data - Replace with actual API call
  useEffect(() => {
    // Simulated API call
    const fetchEventData = async () => {
      // This would be replaced with actual API call
      const mockData = {
        id: eventId,
        title: "Abdominal Cases MDC",
        patients: [
          {
            id: 1,
            firstName: "Steve",
            lastName: "Jobs",
            mrn: "MRN202501",
            primaryDiagnosis: "Stage 4 Pancreatic Cancer (Neuroendocrine Tumor)",
            careJourney: "Palliative care, pain management, nutritional support",
            teamMembers: ["Dr. Anderson (Oncology)", "Dr. Chen (Palliative)", "Dr. Patel (Nutrition)"],
            nextReviewDate: "2025-02-22",
            description: "Progressive disease with focus on symptom management and quality of life",
            upcomingSessions: [
              {
                id: 1,
                startTime: "2025-02-22T09:00:00",
                participants: ["Dr. Anderson", "Dr. Chen", "Pain Management Team"]
              }
            ]
          },
          {
            id: 2,
            firstName: "Mallikarjun",
            lastName: "Udoshi",
            mrn: "MRN202502",
            primaryDiagnosis: "Stage 4 Colon Cancer",
            careJourney: "Chemotherapy response assessment, metastatic disease management",
            teamMembers: ["Dr. Rodriguez (Oncology)", "Dr. Kumar (Surgery)", "Dr. Lee (Radiation)"],
            nextReviewDate: "2025-02-20",
            description: "Ongoing FOLFOX therapy, managing liver metastases",
            upcomingSessions: [
              {
                id: 2,
                startTime: "2025-02-20T14:00:00",
                participants: ["Dr. Rodriguez", "Dr. Kumar", "Clinical Trial Team"]
              }
            ]
          }
        ]
      };

      setEventData(mockData);
      setSelectedPatient(mockData.patients[0]); // Select first patient by default
      setIsLoading(false);
    };

    fetchEventData();
  }, [eventId]);

  const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'supernote', label: 'SuperNote' },
    { id: 'discussion', label: 'Discussion' }
  ];

  return (
    <div className="min-h-screen bg-gray-900">
      {/* Header */}
      <div className="bg-gray-800 border-b border-gray-700 rounded-t-lg">
        <div className="w-full px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center">
            <button 
              onClick={() => window.history.back()}
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
                className={`
                  whitespace-nowrap py-2 px-4 font-medium text-sm rounded-t-lg transition-colors
                  ${activeTab === tab.id
                    ? 'bg-blue-900/50 text-blue-400 border-b-2 border-blue-500'
                    : 'text-gray-400 hover:text-gray-300 hover:bg-gray-700/50'
                  }
                `}
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
                className={`w-full text-left p-4 rounded-lg mb-2 transition-colors ${
                  selectedPatient?.id === patient.id
                    ? 'bg-blue-900/50 border border-blue-500'
                    : 'bg-gray-700/50 border border-gray-600 hover:bg-gray-700'
                }`}
              >
                <div className="flex justify-between items-start mb-2">
                  <div>
                    <h3 className="text-gray-100 font-medium">
                      {patient.lastName}, {patient.firstName}
                    </h3>
                    <p className="text-sm text-gray-400">MRN: {patient.mrn}</p>
                  </div>
                </div>
                <p className="text-sm text-gray-300 font-medium mb-1">
                  {patient.primaryDiagnosis}
                </p>
                <p className="text-sm text-gray-400 mb-2">
                  {patient.careJourney}
                </p>
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
          {isLoading ? (
            <div className="bg-gray-800 rounded-lg border border-gray-700 shadow-lg p-6">
              <div className="text-center">
                <p className="text-gray-400">Loading...</p>
              </div>
            </div>
          ) : selectedPatient ? (
            <>
              {activeTab === 'overview' && (
                <div className="bg-gray-800 rounded-lg border border-gray-700 shadow-lg p-6">
                  <CaseOverview caseData={{
                    patient: selectedPatient,
                    description: selectedPatient.description,
                    nextReviewDate: selectedPatient.nextReviewDate,
                    teamMembers: selectedPatient.teamMembers,
                    upcomingSessions: selectedPatient.upcomingSessions
                  }} />
                </div>
              )}

              {activeTab === 'supernote' && (
                <div className="bg-gray-800 rounded-lg border border-gray-700 shadow-lg overflow-hidden">
                  <ErrorBoundary>
                    <SuperNoteFollowUp
                      note={{
                        patientId: selectedPatient.id,
                        followUpDetails: {
                          visitInfo: {
                            LastVisit: "",
                            FollowUpReason: "",
                            AppointmentType: ""
                          },
                          intervalHistory: {
                            SymptomsProgress: "",
                            NewSymptoms: "",
                            OverallStatus: ""
                          },
                          treatmentResponse: {
                            MedicationResponse: "",
                            SideEffects: "",
                            Adherence: "",
                            Complications: ""
                          },
                          medicationReview: {
                            CurrentMeds: "",
                            Changes: "",
                            RefillsNeeded: ""
                          },
                          vitalSigns: {
                            BP: "",
                            HR: "",
                            RR: "",
                            Temp: "",
                            Weight: "",
                            BMI: "",
                            PainScore: ""
                          },
                          targetedROS: {
                            PertinentPositive: "",
                            PertinentNegative: "",
                            RelatedSystems: ""
                          },
                          focusedExam: {
                            RelevantSystems: "",
                            SignificantFindings: "",
                            ChangesFromLast: ""
                          },
                          testResults: {
                            NewResults: "",
                            PendingTests: "",
                            OrderedTests: ""
                          },
                          assessment: {
                            ProblemStatus: "",
                            NewProblems: "",
                            RiskFactors: ""
                          },
                          plan: {
                            MedicationChanges: "",
                            NewOrders: "",
                            Referrals: "",
                            Procedures: ""
                          },
                          goalProgress: {
                            ClinicalGoals: "",
                            PatientGoals: "",
                            Barriers: ""
                          },
                          patientEducation: {
                            Topics: "",
                            Understanding: "",
                            Concerns: ""
                          },
                          followUpPlan: {
                            Timing: "",
                            Conditions: "",
                            WarningSign: ""
                          },
                          ebmGuidelines: ""
                        }
                      }}
                      isRecording={false}
                      onStartRecording={() => {}}
                      onStopRecording={() => {}}
                      onSave={() => {}}
                      onNoteChange={() => {}}
                    />
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
                          <button 
                            onClick={() => window.location.reload()}
                            className="mt-2 px-4 py-2 bg-red-900 text-red-100 rounded hover:bg-red-800"
                          >
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
            </>
          ) : (
            <div className="bg-gray-800 rounded-lg border border-gray-700 shadow-lg p-6">
              <div className="text-center">
                <p className="text-gray-400">No patient selected</p>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default Collaboration;
