import React from 'react';
import { TrendingUp, Calendar } from 'lucide-react';

const jobsData = {
  diagnosisDate: "2024-07-15",
  markers: [
    {
      name: "Chromogranin A",
      unit: "ng/mL",
      referenceRange: "<100",
      dataPoints: [
        { date: "2024-07-15", value: 250, event: "Initial Diagnosis" },
        { date: "2024-09-01", value: 325 },
        { date: "2024-10-15", value: 375, event: "Started Everolimus" },
        { date: "2024-11-01", value: 425 },
        { date: "2024-12-01", value: 525 },
        { date: "2025-01-03", value: 650 },
        { date: "2025-02-07", value: 985, event: "Disease Progression" }
      ]
    },
    {
      name: "5-HIAA",
      unit: "mg/24h",
      referenceRange: "<10",
      dataPoints: [
        { date: "2024-07-15", value: 15, event: "Initial Diagnosis" },
        { date: "2024-09-01", value: 18 },
        { date: "2024-10-15", value: 22, event: "Started Everolimus" },
        { date: "2024-11-01", value: 25 },
        { date: "2024-12-01", value: 28 },
        { date: "2025-01-03", value: 32 },
        { date: "2025-02-07", value: 45, event: "Disease Progression" }
      ]
    },
    {
      name: "Total Bilirubin",
      unit: "mg/dL",
      referenceRange: "0.3-1.2",
      dataPoints: [
        { date: "2024-07-15", value: 0.8, event: "Initial Diagnosis" },
        { date: "2024-09-01", value: 0.9 },
        { date: "2024-10-15", value: 1.1, event: "Started Everolimus" },
        { date: "2024-11-01", value: 1.1 },
        { date: "2024-12-01", value: 1.4 },
        { date: "2025-01-03", value: 1.8 },
        { date: "2025-02-07", value: 4.2, event: "Disease Progression" }
      ]
    },
    {
      name: "Albumin",
      unit: "g/dL",
      referenceRange: "3.4-5.4",
      dataPoints: [
        { date: "2024-07-15", value: 4.0, event: "Initial Diagnosis" },
        { date: "2024-09-01", value: 3.8 },
        { date: "2024-10-15", value: 3.5, event: "Started Everolimus" },
        { date: "2024-11-01", value: 3.5 },
        { date: "2024-12-01", value: 3.3 },
        { date: "2025-01-03", value: 3.2 },
        { date: "2025-02-07", value: 2.8, event: "Disease Progression" }
      ]
    }
  ]
};

const udoshiData = {
  diagnosisDate: "2024-09-01",
  markers: [
    {
      name: "CEA",
      unit: "ng/mL",
      referenceRange: "<5",
      dataPoints: [
        { date: "2024-09-01", value: 225, event: "Initial Diagnosis" },
        { date: "2024-09-15", value: 200, event: "Primary Resection" },
        { date: "2024-10-01", value: 175 },
        { date: "2024-10-15", value: 150, event: "Started FOLFOX" },
        { date: "2024-11-15", value: 125 },
        { date: "2024-12-15", value: 85 },
        { date: "2025-02-05", value: 45 }
      ]
    },
    {
      name: "CA 19-9",
      unit: "U/mL",
      referenceRange: "<37",
      dataPoints: [
        { date: "2024-09-01", value: 85, event: "Initial Diagnosis" },
        { date: "2024-09-15", value: 75, event: "Primary Resection" },
        { date: "2024-10-01", value: 65 },
        { date: "2024-10-15", value: 55, event: "Started FOLFOX" },
        { date: "2024-11-15", value: 48 },
        { date: "2024-12-15", value: 42 },
        { date: "2025-02-05", value: 38 }
      ]
    },
    {
      name: "Hemoglobin",
      unit: "g/dL",
      referenceRange: "13.5-17.5",
      dataPoints: [
        { date: "2024-09-01", value: 12.5, event: "Initial Diagnosis" },
        { date: "2024-09-15", value: 11.8, event: "Primary Resection" },
        { date: "2024-10-01", value: 11.2 },
        { date: "2024-10-15", value: 10.8, event: "Started FOLFOX" },
        { date: "2024-11-15", value: 10.8 },
        { date: "2024-12-15", value: 11.0 },
        { date: "2025-02-05", value: 11.2 }
      ]
    },
    {
      name: "Platelets",
      unit: "K/µL",
      referenceRange: "150-450",
      dataPoints: [
        { date: "2024-09-01", value: 185, event: "Initial Diagnosis" },
        { date: "2024-09-15", value: 165, event: "Primary Resection" },
        { date: "2024-10-01", value: 155 },
        { date: "2024-10-15", value: 145, event: "Started FOLFOX" },
        { date: "2024-11-15", value: 142 },
        { date: "2024-12-15", value: 145 },
        { date: "2025-02-05", value: 165 }
      ]
    }
  ]
};

const PrognosisView = ({ patientId }) => {
  const data = patientId === 1 ? jobsData : udoshiData;
  
  const getChangeStatus = (marker, current, initial) => {
    const percentChange = ((current - initial) / initial) * 100;
    const absChange = Math.abs(percentChange);
    
    // Define which markers are "bad when increasing"
    const badWhenIncreasing = {
      "Chromogranin A": true,
      "5-HIAA": true,
      "Total Bilirubin": true,
      "CEA": true,
      "CA 19-9": true,
      "Hemoglobin": false,
      "Platelets": false,
      "Albumin": false
    };

    const isBadIncrease = badWhenIncreasing[marker.name];
    const isIncrease = percentChange > 0;

    if (absChange <= 25) {
      return {
        color: 'text-yellow-400',
        text: `${percentChange.toFixed(1)}% (Moderate Change)`
      };
    }

    if (isBadIncrease) {
      if (isIncrease) {
        return {
          color: 'text-red-400',
          text: `+${percentChange.toFixed(1)}% (Critical Increase)`
        };
      } else {
        return {
          color: 'text-green-400',
          text: `${percentChange.toFixed(1)}% (Significant Improvement)`
        };
      }
    } else {
      if (isIncrease) {
        return {
          color: 'text-green-400',
          text: `+${percentChange.toFixed(1)}% (Significant Improvement)`
        };
      } else {
        return {
          color: 'text-red-400',
          text: `${percentChange.toFixed(1)}% (Severe Decline)`
        };
      }
    }
  };

  const getMinMaxValues = (marker) => {
    const values = marker.dataPoints.map(dp => dp.value);
    return {
      min: Math.min(...values),
      max: Math.max(...values)
    };
  };

  const getValueStatus = (marker, value) => {
    const ranges = {
      "Chromogranin A": { normal: 100, elevated: 500 },
      "5-HIAA": { normal: 10, elevated: 30 },
      "Total Bilirubin": { normal: 1.2, elevated: 3.0 },
      "CEA": { normal: 5, elevated: 100 },
      "CA 19-9": { normal: 37, elevated: 100 },
      "Hemoglobin": { normal: [13.5, 17.5], elevated: [11, 13.5] },
      "Platelets": { normal: [150, 450], elevated: [100, 150] },
      "Albumin": { normal: [3.4, 5.4], elevated: [2.8, 3.4] }
    };

    const range = ranges[marker.name];
    if (Array.isArray(range?.normal)) {
      if (value >= range.normal[0] && value <= range.normal[1]) return "normal";
      if (value >= range.elevated[0] && value < range.normal[0]) return "elevated";
      return "critical";
    } else {
      if (value <= range.normal) return "normal";
      if (value <= range.elevated) return "elevated";
      return "critical";
    }
  };

  const getStatusColor = (status, type = 'fill') => {
    const colors = {
      normal: { fill: 'fill-green-500', stroke: 'stroke-green-500' },
      elevated: { fill: 'fill-yellow-500', stroke: 'stroke-yellow-500' },
      critical: { fill: 'fill-red-500', stroke: 'stroke-red-500' }
    };
    return colors[status][type];
  };

  const renderGraph = (marker) => {
    const { min, max } = getMinMaxValues(marker);
    const range = max - min;
    const padding = range * 0.1;
    const graphMin = min - padding;
    const graphMax = max + padding;
    const graphRange = graphMax - graphMin;

    // Get earliest and latest dates
    const dates = marker.dataPoints.map(dp => new Date(dp.date));
    const startDate = new Date(Math.min(...dates));
    const endDate = new Date(Math.max(...dates));
    const timeRange = endDate - startDate;

    // Calculate grid intervals
    const numHorizontalLines = 5;
    const numVerticalLines = Math.ceil(timeRange / (1000 * 60 * 60 * 24 * 30)) + 1; // One line per month

    return (
      <div key={marker.name} className="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div className="flex justify-between items-start mb-4">
          <div>
            <h3 className="text-lg font-medium text-gray-100">{marker.name}</h3>
            <p className="text-sm text-gray-400">Reference Range: {marker.referenceRange} {marker.unit}</p>
          </div>
          <div className="text-right">
            <div className="text-sm text-gray-400">Total Change</div>
            <div className={`font-medium ${
              getChangeStatus(
                marker,
                marker.dataPoints[marker.dataPoints.length - 1].value,
                marker.dataPoints[0].value
              ).color
            }`}>
              {getChangeStatus(
                marker,
                marker.dataPoints[marker.dataPoints.length - 1].value,
                marker.dataPoints[0].value
              ).text}
            </div>
          </div>
        </div>

        <div className="relative h-48 mt-4">
          {/* Graph Area */}
          <div className="absolute inset-0">
            {/* Data Points and Lines */}
            <svg className="w-full h-full">
              {/* Horizontal Grid Lines */}
              {Array.from({ length: numHorizontalLines }).map((_, index) => {
                const y = (index / (numHorizontalLines - 1)) * 100;
                return (
                  <line
                    key={`h-${index}`}
                    x1="0"
                    y1={`${y}%`}
                    x2="100%"
                    y2={`${y}%`}
                    className="stroke-gray-700 stroke-1"
                  />
                );
              })}

              {/* Vertical Grid Lines */}
              {Array.from({ length: numVerticalLines }).map((_, index) => {
                const x = (index / (numVerticalLines - 1)) * 100;
                return (
                  <line
                    key={`v-${index}`}
                    x1={`${x}%`}
                    y1="0"
                    x2={`${x}%`}
                    y2="100%"
                    className="stroke-gray-700 stroke-1"
                  />
                );
              })}

              {/* Connect lines between points with gradient color */}
              {marker.dataPoints.map((point, index) => {
                if (index === 0) return null;
                const prevPoint = marker.dataPoints[index - 1];
                const x1 = ((new Date(prevPoint.date) - startDate) / timeRange) * 100;
                const y1 = 100 - (((prevPoint.value - graphMin) / graphRange) * 100);
                const x2 = ((new Date(point.date) - startDate) / timeRange) * 100;
                const y2 = 100 - (((point.value - graphMin) / graphRange) * 100);
                const status = getValueStatus(marker, point.value);
                return (
                  <line
                    key={index}
                    x1={`${x1}%`}
                    y1={`${y1}%`}
                    x2={`${x2}%`}
                    y2={`${y2}%`}
                    className={`${getStatusColor(status, 'stroke')} stroke-2`}
                  />
                );
              })}
              
              {/* Data points */}
              {marker.dataPoints.map((point, index) => {
                const x = ((new Date(point.date) - startDate) / timeRange) * 100;
                const y = 100 - (((point.value - graphMin) / graphRange) * 100);
                return (
                  <g key={index} className="relative group">
                    <g>
                      <circle
                        cx={`${x}%`}
                        cy={`${y}%`}
                        r={getValueStatus(marker, point.value) === 'critical' ? "6" : "4"}
                        className={`${getStatusColor(getValueStatus(marker, point.value))} ${
                          getValueStatus(marker, point.value) === 'critical' && index === marker.dataPoints.length - 1
                            ? 'animate-pulse'
                            : ''
                        }`}
                      />
                      {point.event && (
                        <circle
                          cx={`${x}%`}
                          cy={`${y}%`}
                          r="8"
                          className="fill-none stroke-yellow-500 stroke-2"
                        />
                      )}
                    </g>
                    
                    {/* Tooltip */}
                    <g className="opacity-0 group-hover:opacity-100 transition-opacity">
                      <rect
                        x={`${x}%`}
                        y={`${y - 12}%`}
                        width="120"
                        height="40"
                        rx="4"
                        transform="translate(-60, -40)"
                        className="fill-gray-900"
                      />
                      <text
                        x={`${x}%`}
                        y={`${y - 12}%`}
                        transform="translate(0, -20)"
                        className="text-xs fill-gray-100 text-center"
                      >
                        <tspan x={`${x}%`} dy="-0.5em" text-anchor="middle">
                          {point.value} {marker.unit}
                        </tspan>
                        <tspan x={`${x}%`} dy="1.2em" text-anchor="middle">
                          {new Date(point.date).toLocaleDateString()}
                        </tspan>
                        {point.event && (
                          <tspan x={`${x}%`} dy="1.2em" text-anchor="middle" className="fill-yellow-500">
                            {point.event}
                          </tspan>
                        )}
                      </text>
                    </g>
                  </g>
                );
              })}
            </svg>
          </div>
        </div>

        {/* X-axis labels */}
        <div className="flex justify-between mt-2 text-sm text-gray-400">
          <span>{new Date(marker.dataPoints[0].date).toLocaleDateString()}</span>
          <span>{new Date(marker.dataPoints[marker.dataPoints.length - 1].date).toLocaleDateString()}</span>
        </div>
      </div>
    );
  };

  return (
    <div className="space-y-6">
      {/* Disease Timeline Header */}
      <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div className="flex items-center gap-2 mb-2">
          <Calendar className="w-5 h-5 text-blue-400" />
          <h2 className="text-xl font-semibold text-gray-100">Disease Timeline</h2>
        </div>
        <p className="text-gray-300">
          Initial Diagnosis: {new Date(data.diagnosisDate).toLocaleDateString()}
          <span className="text-gray-400 ml-2">
            ({Math.round((new Date() - new Date(data.diagnosisDate)) / (1000 * 60 * 60 * 24 * 30))} months ago)
          </span>
        </p>
      </div>

      {/* Marker Graphs */}
      <div className="grid grid-cols-2 gap-6">
        {data.markers.map(marker => renderGraph(marker))}
      </div>

      {/* Prognosis Summary */}
      <div className={`${
        patientId === 1
          ? 'bg-red-900/30 border-red-700'
          : 'bg-green-900/30 border-green-700'
      } border rounded-lg p-4`}>
        <div className="flex items-center gap-2 mb-2">
          <TrendingUp className={`w-5 h-5 ${
            patientId === 1 ? 'text-red-400' : 'text-green-400'
          }`} />
          <h2 className="text-lg font-semibold text-gray-100">Trend Analysis</h2>
        </div>
        <p className={patientId === 1 ? 'text-red-200' : 'text-green-200'}>
          {patientId === 1
            ? "Progressive disease with marked elevation in tumor markers and declining liver function. Chromogranin A shows exponential increase, particularly after disease progression. Albumin trending down indicates deteriorating synthetic function."
            : "Positive treatment response with significant reduction in tumor markers. CEA shows consistent decline (80% reduction from baseline). Hematologic parameters stabilizing with supportive care."}
        </p>
      </div>
    </div>
  );
};

export default PrognosisView;
