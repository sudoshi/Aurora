import React, { useState } from "react";
import { AlertCircle } from "lucide-react";

const jobsData = {
  visitInfo: {
    title: "Visit Information",
    prompt: "Date of last visit, reason for follow-up, appointment type",
    content: {
      LastVisit: "February 8, 2025",
      FollowUpReason: "Stage 4 Pancreatic Neuroendocrine Tumor - Disease progression evaluation",
      AppointmentType: "Urgent Palliative Care Review"
    },
    icon: "📅",
  },
  intervalHistory: {
    title: "Interval History",
    prompt: "Changes since last visit, symptom progression, new concerns",
    content: {
      SymptomsProgress: "Significant increase in abdominal pain (7-8/10) despite opioid adjustments. Worsening early satiety and weight loss (3kg in past week).",
      NewSymptoms: "New onset jaundice noted 3 days ago. Increasing fatigue and somnolence. Development of ascites with increased abdominal distention.",
      OverallStatus: "Marked decline in performance status. Now spending >50% of time in bed. ECOG status deteriorated from 2 to 3."
    },
    icon: "⏱️",
  },
  treatmentResponse: {
    title: "Treatment Response",
    prompt: "Response to current treatment plan, side effects, adherence",
    content: {
      MedicationResponse: "Disease progression on recent imaging despite Everolimus therapy. Somatostatin analog providing minimal symptom control.",
      SideEffects: "Severe fatigue, anorexia, and mucositis from Everolimus. Constipation from opioids requiring aggressive management.",
      Adherence: "Excellent medication compliance but struggling with oral intake due to early satiety and nausea.",
      Complications: "Development of biliary obstruction. Increasing ascites requiring consideration for paracentesis."
    },
    icon: "📈",
  },
  medicationReview: {
    title: "Medication Review",
    prompt: "Current medications, changes, refill needs",
    content: {
      CurrentMeds: "1. Everolimus 10mg daily\n2. Octreotide LAR 30mg monthly\n3. Morphine SR 60mg q12h\n4. Morphine IR 15mg q4h PRN\n5. Ondansetron 8mg q8h\n6. Dexamethasone 4mg daily\n7. Lactulose 20ml q6h",
      Changes: "Morphine SR dose increased from 30mg to 60mg q12h. Added scheduled ondansetron for nausea control.",
      RefillsNeeded: "Morphine IR and ondansetron supplies low, requiring refills"
    },
    icon: "💊",
  },
  vitalSigns: {
    title: "Vital Signs",
    prompt: "Current vital signs and comparison to last visit",
    content: {
      BP: "98/60 (Previous: 110/70)",
      HR: "92 bpm (Previous: 84)",
      RR: "20, unlabored",
      Temp: "37.2°C",
      Weight: "52 kg (Previous: 55 kg)",
      BMI: "18.1 (Previous: 19.2)",
      PainScore: "7/10 at rest, 9/10 with movement"
    },
    icon: "📊",
  },
  targetedROS: {
    title: "Targeted Review of Systems",
    prompt: "Focused review of relevant systems",
    content: {
      PertinentPositive: "Severe abdominal pain\nProgressive jaundice\nWorsening fatigue\nEarly satiety\nNausea\nAbdominal distention\nAnorexia",
      PertinentNegative: "No fever\nNo acute confusion\nNo diarrhea",
      RelatedSystems: "GI: Worsening symptoms\nHepatic: New jaundice\nNeurological: Increasing fatigue\nNutritional: Severe decline"
    },
    icon: "🔍",
  },
  focusedExam: {
    title: "Focused Physical Exam",
    prompt: "Examination of relevant systems and significant changes",
    content: {
      RelevantSystems: "General: Cachectic, jaundiced\nAbdomen: Distended with shifting dullness, tender hepatomegaly\nSkin: Mild peripheral edema, no rash\nNeuro: Alert but fatigued",
      SignificantFindings: "New ascites with positive fluid wave\nIcterus of skin and sclera\nTender hepatomegaly with nodular edge",
      ChangesFromLast: "New jaundice\nWorsening ascites\nDecreased muscle mass\nNew peripheral edema"
    },
    icon: "👨‍⚕️",
  },
  testResults: {
    title: "Test Results",
    prompt: "New results, pending tests, ordered tests",
    content: {
      NewResults: "CT Abdomen (2/7/25): Progressive disease with 30% increase in hepatic metastases, new biliary obstruction\nChromogranin A: 985 ng/mL (increased from 650)\nTotal Bilirubin: 4.2 mg/dL\nAlbumin: 2.8 g/dL",
      PendingTests: "MIBG scan scheduled\nAscites fluid analysis if paracentesis performed",
      OrderedTests: "Weekly CBC, CMP\nPT/INR for coagulopathy assessment"
    },
    icon: "🔬",
  },
  assessment: {
    title: "Assessment",
    prompt: "Problem status updates, new problems, risk factors",
    content: {
      ProblemStatus: "1. Stage 4 Pancreatic NET: Progressive disease with hepatic failure\n2. Malignant biliary obstruction: New\n3. Cancer cachexia: Worsening\n4. Chronic pain: Poorly controlled",
      NewProblems: "Biliary obstruction\nAscites\nWorsening malnutrition",
      RiskFactors: "High risk for hepatic failure\nIncreasing frailty\nSevere malnutrition"
    },
    icon: "📋",
  },
  plan: {
    title: "Plan Updates",
    prompt: "Medication changes, new orders, referrals, procedures",
    content: {
      MedicationChanges: "1. Discontinue Everolimus due to disease progression\n2. Increase dexamethasone to 8mg daily\n3. Add scheduled metoclopramide",
      NewOrders: "Urgent ERCP evaluation\nParacentesis if symptoms worsen\nNutritional support consultation",
      Referrals: "Interventional radiology for biliary stent evaluation\nHospice evaluation",
      Procedures: "ERCP pending evaluation\nPossible paracentesis"
    },
    icon: "📝",
  },
  goalProgress: {
    title: "Goal Progress",
    prompt: "Progress toward clinical and patient-specific goals",
    content: {
      ClinicalGoals: "Transition to comfort-focused care\nSymptom control optimization\nQuality of life preservation",
      PatientGoals: "Maintain lucidity for family time\nAvoid hospitalization if possible\nComplete advance care planning",
      Barriers: "Rapid disease progression\nComplicated symptom management\nNutritional challenges"
    },
    icon: "🎯",
  },
  patientEducation: {
    title: "Patient Education",
    prompt: "Topics discussed, understanding, concerns addressed",
    content: {
      Topics: "Disease progression discussion\nHospice care introduction\nSymptom management strategies",
      Understanding: "Patient and family understand poor prognosis\nAcknowledge transition to comfort care\nClear about DNR status",
      Concerns: "Family seeking support for home care\nQuestions about expected course\nAnxiety about pain control"
    },
    icon: "📖",
  },
  followUpPlan: {
    title: "Follow-up Plan",
    prompt: "Next visit timing, conditions for earlier return",
    content: {
      Timing: "Hospice evaluation within 24 hours\nPalliative care follow-up in 2 days\nWeekly home visits planned",
      Conditions: "Return immediately for: Severe pain crisis, Acute confusion, Rapid decline",
      WarningSign: "Worsening jaundice\nSevere abdominal pain\nMental status changes"
    },
    icon: "📅",
  },
  ebmGuidelines: {
    title: "EBM Guidelines",
    prompt: "Evidence-based measures addressed during visit",
    content: "- NCCN Guidelines for NET: Disease progression confirmed\n- WHO Pain Ladder: Requiring step 3 interventions\n- ASCO Guidelines for Palliative Care Integration\n- Hospice referral criteria met",
    icon: "📊",
  },
};

const udoshiData = {
  visitInfo: {
    title: "Visit Information",
    prompt: "Date of last visit, reason for follow-up, appointment type",
    content: {
      LastVisit: "February 10, 2025",
      FollowUpReason: "Stage 4 Colon Cancer - Chemotherapy Response Assessment",
      AppointmentType: "Treatment Monitoring Visit"
    },
    icon: "📅",
  },
  intervalHistory: {
    title: "Interval History",
    prompt: "Changes since last visit, symptom progression, new concerns",
    content: {
      SymptomsProgress: "Moderate improvement in abdominal pain (3/10 from 6/10). Stable energy levels with supportive care.",
      NewSymptoms: "Grade 2 peripheral neuropathy from oxaliplatin. Mild hand-foot syndrome. No new bowel changes.",
      OverallStatus: "Maintaining ECOG 1 status. Able to perform most daily activities with minimal assistance."
    },
    icon: "⏱️",
  },
  treatmentResponse: {
    title: "Treatment Response",
    prompt: "Response to current treatment plan, side effects, adherence",
    content: {
      MedicationResponse: "Partial response to FOLFOX + Bevacizumab. CEA trending down (85 → 45 ng/mL). Liver metastases showing 30% reduction.",
      SideEffects: "Grade 2 neuropathy, Grade 1 fatigue, Grade 1 hand-foot syndrome. Managing cold sensitivity.",
      Adherence: "Completed 8 of 12 planned cycles. No dose reductions needed. Good tolerance overall.",
      Complications: "No major complications. Neuropathy being monitored closely."
    },
    icon: "📈",
  },
  medicationReview: {
    title: "Medication Review",
    prompt: "Current medications, changes, refill needs",
    content: {
      CurrentMeds: "1. FOLFOX regimen (Cycle 8/12)\n2. Bevacizumab\n3. Ondansetron PRN\n4. Gabapentin 300mg TID\n5. Vitamin B12 supplementation\n6. Iron supplementation",
      Changes: "Added gabapentin for neuropathy management. Increased vitamin B12 dose.",
      RefillsNeeded: "Ondansetron refill needed. Gabapentin adequate supply."
    },
    icon: "💊",
  },
  vitalSigns: {
    title: "Vital Signs",
    prompt: "Current vital signs and comparison to last visit",
    content: {
      BP: "124/76 (Previous: 128/78)",
      HR: "76 bpm, regular",
      RR: "16, unlabored",
      Temp: "36.8°C",
      Weight: "68 kg (Previous: 67.5 kg)",
      BMI: "23.5 (Stable)",
      PainScore: "3/10 (improved from 6/10)"
    },
    icon: "📊",
  },
  targetedROS: {
    title: "Targeted Review of Systems",
    prompt: "Focused review of relevant systems",
    content: {
      PertinentPositive: "Peripheral neuropathy in hands/feet\nMild fatigue\nOccasional nausea\nHand-foot syndrome",
      PertinentNegative: "No diarrhea\nNo bleeding\nNo severe fatigue\nNo new abdominal pain",
      RelatedSystems: "GI: Stable symptoms\nNeuro: Moderate neuropathy\nSkin: Mild changes\nCardiac: No issues"
    },
    icon: "🔍",
  },
  focusedExam: {
    title: "Focused Physical Exam",
    prompt: "Examination of relevant systems and significant changes",
    content: {
      RelevantSystems: "General: Good performance status\nAbdomen: Soft, mild RUQ tenderness\nSkin: Grade 1 hand-foot syndrome\nNeuro: Decreased sensation in fingers/toes",
      SignificantFindings: "Reduced deep tendon reflexes\nMild palmar erythema\nHealed surgical scars",
      ChangesFromLast: "Improved abdominal findings\nStable neuropathy\nNew mild skin changes"
    },
    icon: "👨‍⚕️",
  },
  testResults: {
    title: "Test Results",
    prompt: "New results, pending tests, ordered tests",
    content: {
      NewResults: "CT Chest/Abdomen/Pelvis (2/5/25): 30% reduction in liver metastases, no new lesions\nCEA: 45 ng/mL (Previous: 85 ng/mL)\nCBC: Stable\nCMP: Normal LFTs",
      PendingTests: "None",
      OrderedTests: "Next CT scan after cycle 10\nRoutine labs before next cycle"
    },
    icon: "🔬",
  },
  assessment: {
    title: "Assessment",
    prompt: "Problem status updates, new problems, risk factors",
    content: {
      ProblemStatus: "1. Stage 4 Colon Cancer: Partial response to therapy\n2. Liver Metastases: 30% reduction\n3. Chemotherapy side effects: Moderate neuropathy",
      NewProblems: "Hand-foot syndrome\nWorsening neuropathy",
      RiskFactors: "Risk of cumulative neuropathy\nPotential bevacizumab complications"
    },
    icon: "📋",
  },
  plan: {
    title: "Plan Updates",
    prompt: "Medication changes, new orders, referrals, procedures",
    content: {
      MedicationChanges: "Continue FOLFOX + Bevacizumab\nMaintain gabapentin dose\nAdd topical treatment for hand-foot syndrome",
      NewOrders: "Cycle 9 chemotherapy scheduled\nRoutine labs\nNeuropathy monitoring",
      Referrals: "Physical therapy for neuropathy management\nDermatology for skin care",
      Procedures: "Port maintenance scheduled"
    },
    icon: "📝",
  },
  goalProgress: {
    title: "Goal Progress",
    prompt: "Progress toward clinical and patient-specific goals",
    content: {
      ClinicalGoals: "Tumor response achieved\nTolerable side effect profile\nMaintaining performance status",
      PatientGoals: "Continuing work part-time\nMaintaining independence\nParticipating in family events",
      Barriers: "Neuropathy affecting daily activities\nFatigue limiting exercise"
    },
    icon: "🎯",
  },
  patientEducation: {
    title: "Patient Education",
    prompt: "Topics discussed, understanding, concerns addressed",
    content: {
      Topics: "Neuropathy management\nSkin care for hand-foot syndrome\nNutritional guidance",
      Understanding: "Good comprehension of treatment plan\nAware of side effect management\nFollowing precautions appropriately",
      Concerns: "Questions about long-term neuropathy\nWork-life balance discussion"
    },
    icon: "📖",
  },
  followUpPlan: {
    title: "Follow-up Plan",
    prompt: "Next visit timing, conditions for earlier return",
    content: {
      Timing: "Cycle 9 chemotherapy in 1 week\nFollow-up visit in 2 weeks\nCT scan after cycle 10",
      Conditions: "Return sooner for: Fever, Severe neuropathy, GI symptoms",
      WarningSign: "Fever >38.3°C\nSevere abdominal pain\nBleeding\nChest pain"
    },
    icon: "📅",
  },
  ebmGuidelines: {
    title: "EBM Guidelines",
    prompt: "Evidence-based measures addressed during visit",
    content: "- NCCN Guidelines for Colon Cancer: Following standard protocol\n- ASCO Guidelines for Neuropathy Management\n- Bevacizumab monitoring guidelines followed\n- Standard response criteria (RECIST) applied",
    icon: "📊",
  },
};

export function SuperNoteFollowUp({ note }) {
  const [activeSection, setActiveSection] = useState("visitInfo");
  const sections = note.patientId === 1 ? jobsData : udoshiData;

  const renderSectionContent = (sectionKey) => {
    const section = sections[sectionKey];
    const content = section.content;

    if (typeof content === "string") {
      return (
        <div className="whitespace-pre-line text-gray-100">
          {content}
        </div>
      );
    }

    return (
      <div className="space-y-4">
        {Object.entries(content).map(([key, value]) => (
          <div key={key} className="border-l-4 border-blue-500/50 pl-4">
            <h4 className="font-semibold mb-2 text-gray-100">
              {key.replace(/([A-Z])/g, " $1").trim()}
            </h4>
            <div className="text-gray-100 whitespace-pre-line">
              {value}
            </div>
          </div>
        ))}
      </div>
    );
  };

  return (
    <div className="relative w-full h-[calc(100vh-180px)] flex flex-col">
      <div className="bg-gray-800 border-b border-gray-700 p-4 flex-none">
        <div className="flex justify-between items-center text-gray-100">
          <div className="flex items-center space-x-4">
            <h2 className="text-xl font-semibold">
              Latest Summary
            </h2>
          </div>
        </div>
      </div>

      <div className="flex-1 overflow-hidden">
        <div className="h-full flex gap-4 p-6">
          <div className="w-80 min-w-[320px] max-w-md space-y-2 overflow-y-auto">
            {Object.entries(sections).map(([key, section]) => (
              <button
                key={key}
                className={`w-full p-2 rounded-lg flex items-center transition-colors ${
                  activeSection === key
                    ? "bg-blue-900/50 text-gray-100"
                    : "text-gray-400 hover:bg-gray-700/50 hover:text-gray-300"
                }`}
                onClick={() => setActiveSection(key)}
              >
                <span className="mr-2">{section.icon}</span>
                {section.title}
              </button>
            ))}
          </div>

          <div className="flex-1 min-w-0 space-y-4 overflow-y-auto">
            <div className="bg-gray-700/30 border border-gray-600 rounded-lg p-4 flex items-start">
              <AlertCircle className="h-5 w-5 text-blue-400 mr-3 mt-0.5" />
              <p className="text-gray-300">
                {sections[activeSection].prompt}
              </p>
            </div>

            <div className="bg-gray-800 border border-gray-700 rounded-lg p-6">
              {renderSectionContent(activeSection)}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
