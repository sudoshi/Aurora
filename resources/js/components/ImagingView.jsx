import React from 'react';
import { Calendar, TrendingUp } from 'lucide-react';

const jobsData = {
  studies: [
    {
      date: "2025-02-07",
      type: "CT Abdomen/Pelvis with contrast",
      findings: "Progressive disease with 30% increase in hepatic metastases. New biliary obstruction. Multiple hypervascular lesions throughout liver, largest measuring 4.2 x 3.8 cm (previously 3.2 x 2.9 cm). Moderate ascites.",
      comparison: "Compared to 2025-01-03",
      key_measurements: [
        { location: "Liver Segment VII", current: "4.2 x 3.8 cm", previous: "3.2 x 2.9 cm", change: "+31%" },
        { location: "Liver Segment IV", current: "2.8 x 2.5 cm", previous: "2.1 x 1.9 cm", change: "+33%" }
      ]
    },
    {
      date: "2025-01-03",
      type: "CT Chest/Abdomen/Pelvis with contrast",
      findings: "Interval progression of hepatic metastases. No pulmonary metastases. Primary pancreatic mass stable at 2.1 x 1.8 cm. Mild ascites.",
      comparison: "Compared to 2024-11-15",
      key_measurements: [
        { location: "Liver Segment VII", current: "3.2 x 2.9 cm", previous: "2.5 x 2.2 cm", change: "+28%" },
        { location: "Liver Segment IV", current: "2.1 x 1.9 cm", previous: "1.6 x 1.4 cm", change: "+31%" }
      ]
    },
    {
      date: "2025-01-15",
      type: "MIBG Scan",
      findings: "Intense uptake in multiple hepatic lesions consistent with metastatic neuroendocrine tumor. No other sites of metastatic disease identified.",
      comparison: "First MIBG study",
      key_measurements: []
    },
    {
      date: "2024-12-20",
      type: "Octreotide Scan",
      findings: "Somatostatin receptor-positive disease in liver and pancreas. No other sites of metastatic disease.",
      comparison: "First Octreotide study",
      key_measurements: []
    }
  ],
  volumetrics: [
    { date: "2025-02-07", total_tumor_volume: "158.3 cc", change: "+30%" },
    { date: "2025-01-03", total_tumor_volume: "121.8 cc", change: "+28%" },
    { date: "2024-11-15", total_tumor_volume: "95.2 cc", change: "baseline" }
  ]
};

const udoshiData = {
  studies: [
    {
      date: "2025-02-05",
      type: "CT Chest/Abdomen/Pelvis with contrast",
      findings: "Partial response to therapy. 30% reduction in liver metastases. No new lesions. Primary site surgical changes stable. No pulmonary metastases.",
      comparison: "Compared to 2024-12-15",
      key_measurements: [
        { location: "Liver Segment VI", current: "2.8 x 2.5 cm", previous: "4.0 x 3.6 cm", change: "-30%" },
        { location: "Liver Segment III", current: "1.9 x 1.7 cm", previous: "2.7 x 2.4 cm", change: "-29%" }
      ]
    },
    {
      date: "2025-01-20",
      type: "PET/CT",
      findings: "Decreased FDG avidity in all target lesions. SUV max reduced from 12.3 to 7.8 in index lesion. No new hypermetabolic lesions.",
      comparison: "Compared to 2024-12-01",
      key_measurements: [
        { location: "Liver Segment VI", current: "SUV 7.8", previous: "SUV 12.3", change: "-37%" }
      ]
    },
    {
      date: "2024-12-15",
      type: "CT Chest/Abdomen/Pelvis with contrast",
      findings: "Multiple hepatic metastases. Post-surgical changes from primary resection. No pulmonary metastases.",
      comparison: "Post-operative baseline",
      key_measurements: [
        { location: "Liver Segment VI", current: "4.0 x 3.6 cm", previous: "N/A", change: "N/A" },
        { location: "Liver Segment III", current: "2.7 x 2.4 cm", previous: "N/A", change: "N/A" }
      ]
    }
  ],
  volumetrics: [
    { date: "2025-02-05", total_tumor_volume: "42.6 cc", change: "-30%" },
    { date: "2024-12-15", total_tumor_volume: "60.9 cc", change: "baseline" }
  ],
  recist: [
    {
      date: "2025-02-05",
      target_lesions: [
        { location: "Liver Segment VI", size: "2.8 cm" },
        { location: "Liver Segment III", size: "1.9 cm" }
      ],
      sum: "4.7 cm",
      response: "Partial Response (-30%)",
      non_target: "Present, stable",
      new_lesions: "None"
    },
    {
      date: "2024-12-15",
      target_lesions: [
        { location: "Liver Segment VI", size: "4.0 cm" },
        { location: "Liver Segment III", size: "2.7 cm" }
      ],
      sum: "6.7 cm",
      response: "Baseline",
      non_target: "Present",
      new_lesions: "N/A"
    }
  ]
};

const ImagingView = ({ patientId }) => {
  const data = patientId === 1 ? jobsData : udoshiData;

  return (
    <div className="space-y-6">
      {/* Timeline View */}
      <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h2 className="text-xl font-semibold text-gray-100 mb-4">Imaging Timeline</h2>
        <div className="space-y-6">
          {data.studies.map((study, index) => (
            <div key={index} className="relative">
              <div className="flex items-start gap-4">
                <div className="flex-none pt-1">
                  <div className="bg-blue-600 rounded-full p-2">
                    <Calendar className="w-4 h-4 text-white" />
                  </div>
                </div>
                <div className="flex-1 bg-gray-700/50 rounded-lg p-4">
                  <div className="flex justify-between items-start mb-2">
                    <h3 className="font-semibold text-gray-100">{study.type}</h3>
                    <span className="text-sm text-gray-400">{study.date}</span>
                  </div>
                  <p className="text-gray-300 mb-2">{study.findings}</p>
                  {study.key_measurements.length > 0 && (
                    <div className="mt-3 border-t border-gray-600 pt-3">
                      <h4 className="text-sm font-medium text-gray-100 mb-2">Key Measurements</h4>
                      <div className="grid grid-cols-4 gap-4 text-sm">
                        <div className="text-gray-400">Location</div>
                        <div className="text-gray-400">Current</div>
                        <div className="text-gray-400">Previous</div>
                        <div className="text-gray-400">Change</div>
                        {study.key_measurements.map((measurement, idx) => (
                          <React.Fragment key={idx}>
                            <div className="text-gray-300">{measurement.location}</div>
                            <div className="text-gray-300">{measurement.current}</div>
                            <div className="text-gray-300">{measurement.previous}</div>
                            <div className={`${
                              measurement.change.startsWith('+') 
                                ? 'text-red-400' 
                                : measurement.change.startsWith('-')
                                  ? 'text-green-400'
                                  : 'text-gray-300'
                            }`}>
                              {measurement.change}
                            </div>
                          </React.Fragment>
                        ))}
                      </div>
                    </div>
                  )}
                  <div className="mt-2 text-sm text-gray-400">{study.comparison}</div>
                </div>
              </div>
              {index < data.studies.length - 1 && (
                <div className="absolute left-[23px] top-[48px] bottom-[-24px] w-px bg-gray-700" />
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Volumetrics */}
      <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div className="flex items-center gap-2 mb-4">
          <h2 className="text-xl font-semibold text-gray-100">Tumor Volumetrics</h2>
          <TrendingUp className="w-5 h-5 text-gray-400" />
        </div>
        <div className="space-y-4">
          {data.volumetrics.map((volume, index) => (
            <div key={index} className="flex items-center justify-between bg-gray-700/50 rounded-lg p-4">
              <span className="text-gray-300">{volume.date}</span>
              <span className="text-gray-100 font-medium">{volume.total_tumor_volume}</span>
              <span className={`${
                volume.change.startsWith('+') 
                  ? 'text-red-400' 
                  : volume.change.startsWith('-')
                    ? 'text-green-400'
                    : 'text-gray-300'
              }`}>
                {volume.change}
              </span>
            </div>
          ))}
        </div>
      </div>

      {/* RECIST Criteria (Colon Cancer Only) */}
      {patientId === 2 && (
        <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
          <h2 className="text-xl font-semibold text-gray-100 mb-4">RECIST 1.1 Assessment</h2>
          <div className="space-y-4">
            {data.recist.map((assessment, index) => (
              <div key={index} className="bg-gray-700/50 rounded-lg p-4">
                <div className="flex justify-between items-start mb-3">
                  <h3 className="font-medium text-gray-100">{assessment.date}</h3>
                  <span className={`px-3 py-1 rounded-full text-sm ${
                    assessment.response.includes('Partial Response')
                      ? 'bg-green-900/50 text-green-300'
                      : 'bg-gray-600/50 text-gray-300'
                  }`}>
                    {assessment.response}
                  </span>
                </div>
                <div className="space-y-3">
                  <div>
                    <h4 className="text-sm font-medium text-gray-400 mb-2">Target Lesions</h4>
                    <div className="grid grid-cols-2 gap-2">
                      {assessment.target_lesions.map((lesion, idx) => (
                        <div key={idx} className="text-gray-300">
                          {lesion.location}: {lesion.size}
                        </div>
                      ))}
                    </div>
                  </div>
                  <div className="border-t border-gray-600 pt-2">
                    <div className="grid grid-cols-2 gap-2 text-sm">
                      <div className="text-gray-400">Sum of Diameters:</div>
                      <div className="text-gray-300">{assessment.sum}</div>
                      <div className="text-gray-400">Non-target Lesions:</div>
                      <div className="text-gray-300">{assessment.non_target}</div>
                      <div className="text-gray-400">New Lesions:</div>
                      <div className="text-gray-300">{assessment.new_lesions}</div>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default ImagingView;
