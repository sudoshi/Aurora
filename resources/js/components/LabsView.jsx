import React from 'react';
import { TrendingUp, AlertCircle } from 'lucide-react';

const jobsData = {
  tumorMarkers: [
    {
      date: "2025-02-07",
      markers: {
        "Chromogranin A": { value: "985 ng/mL", reference: "<100 ng/mL", previous: "650 ng/mL", change: "+51%" },
        "5-HIAA": { value: "45 mg/24h", reference: "<10 mg/24h", previous: "32 mg/24h", change: "+41%" }
      }
    },
    {
      date: "2025-01-03",
      markers: {
        "Chromogranin A": { value: "650 ng/mL", reference: "<100 ng/mL", previous: "425 ng/mL", change: "+53%" },
        "5-HIAA": { value: "32 mg/24h", reference: "<10 mg/24h", previous: "25 mg/24h", change: "+28%" }
      }
    }
  ],
  liverFunction: [
    {
      date: "2025-02-07",
      tests: {
        "Total Bilirubin": { value: "4.2 mg/dL", reference: "0.3-1.2 mg/dL", previous: "1.8 mg/dL", change: "+133%" },
        "Direct Bilirubin": { value: "2.8 mg/dL", reference: "0.0-0.3 mg/dL", previous: "1.1 mg/dL", change: "+155%" },
        "AST": { value: "165 U/L", reference: "10-40 U/L", previous: "98 U/L", change: "+68%" },
        "ALT": { value: "145 U/L", reference: "7-56 U/L", previous: "85 U/L", change: "+71%" },
        "ALP": { value: "385 U/L", reference: "44-147 U/L", previous: "225 U/L", change: "+71%" },
        "Albumin": { value: "2.8 g/dL", reference: "3.4-5.4 g/dL", previous: "3.2 g/dL", change: "-13%" }
      }
    },
    {
      date: "2025-01-03",
      tests: {
        "Total Bilirubin": { value: "1.8 mg/dL", reference: "0.3-1.2 mg/dL", previous: "1.1 mg/dL", change: "+64%" },
        "Direct Bilirubin": { value: "1.1 mg/dL", reference: "0.0-0.3 mg/dL", previous: "0.6 mg/dL", change: "+83%" },
        "AST": { value: "98 U/L", reference: "10-40 U/L", previous: "65 U/L", change: "+51%" },
        "ALT": { value: "85 U/L", reference: "7-56 U/L", previous: "58 U/L", change: "+47%" },
        "ALP": { value: "225 U/L", reference: "44-147 U/L", previous: "165 U/L", change: "+36%" },
        "Albumin": { value: "3.2 g/dL", reference: "3.4-5.4 g/dL", previous: "3.5 g/dL", change: "-9%" }
      }
    }
  ],
  metabolicPanel: [
    {
      date: "2025-02-07",
      tests: {
        "Sodium": { value: "134 mEq/L", reference: "136-145 mEq/L", previous: "136 mEq/L", change: "-1%" },
        "Potassium": { value: "3.8 mEq/L", reference: "3.5-5.1 mEq/L", previous: "4.0 mEq/L", change: "-5%" },
        "Chloride": { value: "98 mEq/L", reference: "98-107 mEq/L", previous: "100 mEq/L", change: "-2%" },
        "CO2": { value: "24 mEq/L", reference: "22-29 mEq/L", previous: "25 mEq/L", change: "-4%" },
        "BUN": { value: "28 mg/dL", reference: "7-20 mg/dL", previous: "22 mg/dL", change: "+27%" },
        "Creatinine": { value: "1.0 mg/dL", reference: "0.6-1.2 mg/dL", previous: "0.9 mg/dL", change: "+11%" }
      }
    }
  ]
};

const udoshiData = {
  tumorMarkers: [
    {
      date: "2025-02-05",
      markers: {
        "CEA": { value: "45 ng/mL", reference: "<5 ng/mL", previous: "85 ng/mL", change: "-47%" },
        "CA 19-9": { value: "38 U/mL", reference: "<37 U/mL", previous: "42 U/mL", change: "-10%" }
      }
    },
    {
      date: "2024-12-15",
      markers: {
        "CEA": { value: "85 ng/mL", reference: "<5 ng/mL", previous: "125 ng/mL", change: "-32%" },
        "CA 19-9": { value: "42 U/mL", reference: "<37 U/mL", previous: "55 U/mL", change: "-24%" }
      }
    }
  ],
  liverFunction: [
    {
      date: "2025-02-05",
      tests: {
        "Total Bilirubin": { value: "0.8 mg/dL", reference: "0.3-1.2 mg/dL", previous: "0.9 mg/dL", change: "-11%" },
        "AST": { value: "32 U/L", reference: "10-40 U/L", previous: "35 U/L", change: "-9%" },
        "ALT": { value: "28 U/L", reference: "7-56 U/L", previous: "30 U/L", change: "-7%" },
        "ALP": { value: "95 U/L", reference: "44-147 U/L", previous: "98 U/L", change: "-3%" },
        "Albumin": { value: "3.8 g/dL", reference: "3.4-5.4 g/dL", previous: "3.7 g/dL", change: "+3%" }
      }
    }
  ],
  cbc: [
    {
      date: "2025-02-05",
      tests: {
        "WBC": { value: "4.8 K/µL", reference: "4.5-11.0 K/µL", previous: "4.2 K/µL", change: "+14%" },
        "Hemoglobin": { value: "11.2 g/dL", reference: "13.5-17.5 g/dL", previous: "10.8 g/dL", change: "+4%" },
        "Platelets": { value: "165 K/µL", reference: "150-450 K/µL", previous: "145 K/µL", change: "+14%" },
        "ANC": { value: "2.8 K/µL", reference: "1.8-7.7 K/µL", previous: "2.4 K/µL", change: "+17%" }
      }
    },
    {
      date: "2024-12-15",
      tests: {
        "WBC": { value: "4.2 K/µL", reference: "4.5-11.0 K/µL", previous: "N/A", change: "N/A" },
        "Hemoglobin": { value: "10.8 g/dL", reference: "13.5-17.5 g/dL", previous: "N/A", change: "N/A" },
        "Platelets": { value: "145 K/µL", reference: "150-450 K/µL", previous: "N/A", change: "N/A" },
        "ANC": { value: "2.4 K/µL", reference: "1.8-7.7 K/µL", previous: "N/A", change: "N/A" }
      }
    }
  ]
};

const LabsView = ({ patientId }) => {
  const data = patientId === 1 ? jobsData : udoshiData;

  const renderLabPanel = (title, dates, getTests) => (
    <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
      <div className="flex items-center gap-2 mb-4">
        <h2 className="text-xl font-semibold text-gray-100">{title}</h2>
        <TrendingUp className="w-5 h-5 text-gray-400" />
      </div>
      <div className="space-y-6">
        {dates.map((date, dateIndex) => (
          <div key={dateIndex} className="bg-gray-700/50 rounded-lg p-4">
            <div className="flex justify-between items-center mb-4">
              <h3 className="font-medium text-gray-100">{date.date}</h3>
            </div>
            <div className="grid grid-cols-6 gap-4 text-sm">
              <div className="text-gray-400">Test</div>
              <div className="text-gray-400">Value</div>
              <div className="text-gray-400">Reference</div>
              <div className="text-gray-400">Previous</div>
              <div className="text-gray-400">Change</div>
              <div className="text-gray-400">Status</div>
              {Object.entries(getTests(date)).map(([name, result], index) => (
                <React.Fragment key={index}>
                  <div className="text-gray-300">{name}</div>
                  <div className="text-gray-100 font-medium">{result.value}</div>
                  <div className="text-gray-400">{result.reference}</div>
                  <div className="text-gray-300">{result.previous}</div>
                  <div className={`${
                    result.change.startsWith('+') 
                      ? 'text-red-400' 
                      : result.change.startsWith('-')
                        ? 'text-green-400'
                        : 'text-gray-300'
                  }`}>
                    {result.change}
                  </div>
                  <div>
                    {(() => {
                      const [min, max] = result.reference.replace(/[^0-9.-]/g, ' ')
                        .trim().split(' ').map(Number);
                      const value = parseFloat(result.value);
                      if (isNaN(value) || isNaN(min) || isNaN(max)) return null;
                      
                      if (value < min) {
                        return <span className="px-2 py-1 rounded-full text-xs bg-blue-900/50 text-blue-300">Low</span>;
                      } else if (value > max) {
                        return <span className="px-2 py-1 rounded-full text-xs bg-red-900/50 text-red-300">High</span>;
                      }
                      return <span className="px-2 py-1 rounded-full text-xs bg-green-900/50 text-green-300">Normal</span>;
                    })()}
                  </div>
                </React.Fragment>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );

  return (
    <div className="space-y-6">
      {/* Tumor Markers */}
      {renderLabPanel(
        "Tumor Markers",
        data.tumorMarkers,
        (date) => date.markers
      )}

      {/* Liver Function */}
      {renderLabPanel(
        "Liver Function Tests",
        data.liverFunction,
        (date) => date.tests
      )}

      {/* CBC or Metabolic Panel */}
      {patientId === 1 
        ? renderLabPanel(
            "Comprehensive Metabolic Panel",
            data.metabolicPanel,
            (date) => date.tests
          )
        : renderLabPanel(
            "Complete Blood Count",
            data.cbc,
            (date) => date.tests
          )
      }

      {/* Alert for Critical Values */}
      {patientId === 1 && (
        <div className="bg-red-900/30 border border-red-700 rounded-lg p-4 flex items-start">
          <AlertCircle className="w-5 h-5 text-red-400 mr-3 mt-0.5 flex-shrink-0" />
          <div>
            <h3 className="font-medium text-red-300 mb-1">Critical Values Alert</h3>
            <p className="text-red-200">
              Total Bilirubin and Liver enzymes show significant elevation. 
              Chromogranin A levels continue to rise indicating disease progression.
            </p>
          </div>
        </div>
      )}
    </div>
  );
};

export default LabsView;
