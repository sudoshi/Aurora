import React from 'react';
import { Calendar, Clock, MessageSquare, Video, Users } from 'lucide-react';

const CaseOverview = ({ caseData }) => {
  return (
    <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 h-[calc(100vh-280px)] overflow-y-auto">
      <div className="col-span-1 md:col-span-2 bg-gray-700/30 rounded-lg p-6 min-w-0">
        <div className="mb-4">
          <div className="flex items-center gap-2 mb-2">
            <h2 className="text-xl font-semibold text-gray-100">
              {caseData.patient.lastName}, {caseData.patient.firstName}
            </h2>
            <span className="text-sm text-gray-400">
              MRN: {caseData.patient.mrn}
            </span>
          </div>
          <p className="text-gray-300">
            Primary Diagnosis: {caseData.patient.primaryDiagnosis}
          </p>
        </div>
        
        <div className="bg-gray-800/50 rounded-lg p-4 mb-4">
          <h3 className="font-medium text-gray-100 mb-2">Case Description</h3>
          <p className="text-gray-300">{caseData.description}</p>
        </div>
        
        <div className="flex items-center gap-4 text-gray-300">
          <span className="flex items-center gap-2">
            <Calendar className="h-4 w-4 text-gray-400" />
            Next Review: {new Date(caseData.nextReviewDate).toLocaleDateString()}
          </span>
          <span className="flex items-center gap-2">
            <Users className="h-4 w-4 text-gray-400" />
            Team Members: {caseData.teamMembers.length}
          </span>
        </div>
      </div>

      <div className="col-span-1 bg-gray-700/30 rounded-lg p-6 min-w-[280px]">
        <h3 className="text-lg font-medium text-gray-100 mb-4">Quick Actions</h3>
        <div className="space-y-2">
          <button className="flex w-full items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 transition-colors">
            <Video className="h-4 w-4" />
            Start Video Session
          </button>
          <button className="flex w-full items-center gap-2 rounded-lg bg-gray-600 px-4 py-2 text-gray-100 hover:bg-gray-500 transition-colors">
            <MessageSquare className="h-4 w-4" />
            Add Discussion
          </button>
        </div>
      </div>

      <div className="col-span-1 bg-gray-700/30 rounded-lg p-6 min-w-[280px]">
        <h3 className="text-lg font-medium text-gray-100 mb-4">Upcoming Sessions</h3>
        <div className="space-y-3">
          {caseData.upcomingSessions.map((session) => (
            <div key={session.id} className="flex items-center gap-2 rounded-lg bg-gray-800/50 p-3">
              <Clock className="h-4 w-4 text-gray-400" />
              <div>
                <p className="font-medium text-gray-100">
                  {new Date(session.startTime).toLocaleDateString()}
                </p>
                <p className="text-sm text-gray-400">
                  {session.participants.length} participants
                </p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default CaseOverview;
