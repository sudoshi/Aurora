import React, { useState, useEffect } from 'react';
import { Calendar, Clock, MessageSquare, Video, Users } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const CaseOverview = ({ caseData }) => {
  return (
    <div className="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-4">
      <Card className="col-span-1 md:col-span-2">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <span className="text-xl font-semibold">{caseData.patient.lastName}, {caseData.patient.firstName}</span>
            <span className="text-sm text-gray-500">MRN: {caseData.patient.mrn}</span>
          </CardTitle>
          <CardDescription>
            Primary Diagnosis: {caseData.patient.primaryDiagnosis}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div className="rounded-lg bg-gray-50 p-4">
              <h3 className="font-medium">Case Description</h3>
              <p className="mt-2 text-gray-600">{caseData.description}</p>
            </div>
            
            <div className="flex items-center gap-4">
              <span className="flex items-center gap-2">
                <Calendar className="h-4 w-4 text-gray-500" />
                Next Review: {new Date(caseData.nextReviewDate).toLocaleDateString()}
              </span>
              <span className="flex items-center gap-2">
                <Users className="h-4 w-4 text-gray-500" />
                Team Members: {caseData.teamMembers.length}
              </span>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card className="col-span-1">
        <CardHeader>
          <CardTitle>Quick Actions</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-2">
            <button className="flex w-full items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
              <Video className="h-4 w-4" />
              Start Video Session
            </button>
            <button className="flex w-full items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200">
              <MessageSquare className="h-4 w-4" />
              Add Discussion
            </button>
          </div>
        </CardContent>
      </Card>

      <Card className="col-span-1">
        <CardHeader>
          <CardTitle>Upcoming Sessions</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {caseData.upcomingSessions.map((session) => (
              <div key={session.id} className="flex items-center gap-2 rounded-lg bg-gray-50 p-2">
                <Clock className="h-4 w-4 text-gray-500" />
                <div>
                  <p className="font-medium">{new Date(session.startTime).toLocaleDateString()}</p>
                  <p className="text-sm text-gray-500">{session.participants.length} participants</p>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default CaseOverview;