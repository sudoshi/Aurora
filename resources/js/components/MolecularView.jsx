import React from 'react';
import { FileText, AlertCircle } from 'lucide-react';

const jobsData = {
  foundationOne: {
    reportDate: "2024-12-10",
    specimen: {
      type: "Liver Biopsy",
      tumorPurity: "60%",
      collectionDate: "2024-12-01"
    },
    genomicFindings: [
      {
        gene: "MEN1",
        alteration: "R171X",
        variantClass: "Pathogenic",
        fdaTherapies: null,
        clinicalTrials: 3
      },
      {
        gene: "DAXX",
        alteration: "S567*",
        variantClass: "Likely Pathogenic",
        fdaTherapies: null,
        clinicalTrials: 2
      },
      {
        gene: "TSC2",
        alteration: "Loss exons 1-3",
        variantClass: "Pathogenic",
        fdaTherapies: ["Everolimus"],
        clinicalTrials: 5
      }
    ],
    biomarkers: {
      "MSI Status": "Stable",
      "Tumor Mutational Burden": "2 mutations/Mb (Low)",
      "PD-L1": "Negative (<1%)"
    }
  },
  guardant360: [
    {
      reportDate: "2025-02-01",
      findings: [
        {
          gene: "MEN1",
          alteration: "R171X",
          alleleFrequency: "5.2%",
          previousFrequency: "3.8%",
          change: "+37%"
        },
        {
          gene: "TSC2",
          alteration: "Loss exons 1-3",
          alleleFrequency: "4.8%",
          previousFrequency: "3.2%",
          change: "+50%"
        }
      ],
      ctDNA: "2.8%"
    },
    {
      reportDate: "2024-12-15",
      findings: [
        {
          gene: "MEN1",
          alteration: "R171X",
          alleleFrequency: "3.8%",
          previousFrequency: "N/A",
          change: "N/A"
        },
        {
          gene: "TSC2",
          alteration: "Loss exons 1-3",
          alleleFrequency: "3.2%",
          previousFrequency: "N/A",
          change: "N/A"
        }
      ],
      ctDNA: "1.9%"
    }
  ]
};

const udoshiData = {
  foundationOne: {
    reportDate: "2024-11-15",
    specimen: {
      type: "Colon Adenocarcinoma",
      tumorPurity: "70%",
      collectionDate: "2024-11-05"
    },
    genomicFindings: [
      {
        gene: "KRAS",
        alteration: "G12D",
        variantClass: "Pathogenic",
        fdaTherapies: null,
        clinicalTrials: 8
      },
      {
        gene: "TP53",
        alteration: "R175H",
        variantClass: "Pathogenic",
        fdaTherapies: null,
        clinicalTrials: 4
      },
      {
        gene: "APC",
        alteration: "R1450*",
        variantClass: "Pathogenic",
        fdaTherapies: null,
        clinicalTrials: 2
      }
    ],
    biomarkers: {
      "MSI Status": "Stable",
      "Tumor Mutational Burden": "4.8 mutations/Mb (Low)",
      "PD-L1": "Negative (<1%)",
      "HRD Score": "16 (Negative)"
    }
  },
  guardant360: [
    {
      reportDate: "2025-02-01",
      findings: [
        {
          gene: "KRAS",
          alteration: "G12D",
          alleleFrequency: "0.8%",
          previousFrequency: "1.5%",
          change: "-47%"
        },
        {
          gene: "TP53",
          alteration: "R175H",
          alleleFrequency: "0.6%",
          previousFrequency: "1.2%",
          change: "-50%"
        }
      ],
      ctDNA: "0.9%"
    },
    {
      reportDate: "2024-12-15",
      findings: [
        {
          gene: "KRAS",
          alteration: "G12D",
          alleleFrequency: "1.5%",
          previousFrequency: "N/A",
          change: "N/A"
        },
        {
          gene: "TP53",
          alteration: "R175H",
          alleleFrequency: "1.2%",
          previousFrequency: "N/A",
          change: "N/A"
        }
      ],
      ctDNA: "1.8%"
    }
  ]
};

const MolecularView = ({ patientId }) => {
  const data = patientId === 1 ? jobsData : udoshiData;

  return (
    <div className="space-y-6">
      {/* FoundationOne Report */}
      <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div className="flex items-center justify-between mb-6">
          <div className="flex items-center gap-2">
            <FileText className="w-5 h-5 text-blue-400" />
            <h2 className="text-xl font-semibold text-gray-100">FoundationOne CDx Report</h2>
          </div>
          <span className="text-gray-400">{data.foundationOne.reportDate}</span>
        </div>

        {/* Specimen Info */}
        <div className="bg-gray-700/50 rounded-lg p-4 mb-6">
          <h3 className="font-medium text-gray-100 mb-3">Specimen Information</h3>
          <div className="grid grid-cols-3 gap-4 text-sm">
            <div>
              <span className="text-gray-400">Type:</span>
              <span className="text-gray-100 ml-2">{data.foundationOne.specimen.type}</span>
            </div>
            <div>
              <span className="text-gray-400">Tumor Purity:</span>
              <span className="text-gray-100 ml-2">{data.foundationOne.specimen.tumorPurity}</span>
            </div>
            <div>
              <span className="text-gray-400">Collection Date:</span>
              <span className="text-gray-100 ml-2">{data.foundationOne.specimen.collectionDate}</span>
            </div>
          </div>
        </div>

        {/* Genomic Findings */}
        <div className="mb-6">
          <h3 className="font-medium text-gray-100 mb-3">Genomic Findings</h3>
          <div className="space-y-3">
            {data.foundationOne.genomicFindings.map((finding, index) => (
              <div key={index} className="bg-gray-700/50 rounded-lg p-4">
                <div className="flex justify-between items-start mb-2">
                  <div>
                    <span className="font-medium text-gray-100">{finding.gene}</span>
                    <span className="text-gray-400 ml-2">{finding.alteration}</span>
                  </div>
                  <span className={`px-2 py-1 rounded-full text-xs ${
                    finding.variantClass === 'Pathogenic'
                      ? 'bg-red-900/50 text-red-300'
                      : 'bg-yellow-900/50 text-yellow-300'
                  }`}>
                    {finding.variantClass}
                  </span>
                </div>
                <div className="text-sm">
                  {finding.fdaTherapies && (
                    <div className="mt-2">
                      <span className="text-gray-400">FDA-Approved Therapies:</span>
                      <span className="text-green-400 ml-2">{finding.fdaTherapies.join(", ")}</span>
                    </div>
                  )}
                  <div className="mt-1">
                    <span className="text-gray-400">Clinical Trials:</span>
                    <span className="text-blue-400 ml-2">{finding.clinicalTrials} available</span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Biomarkers */}
        <div>
          <h3 className="font-medium text-gray-100 mb-3">Biomarker Status</h3>
          <div className="grid grid-cols-2 gap-4">
            {Object.entries(data.foundationOne.biomarkers).map(([key, value], index) => (
              <div key={index} className="bg-gray-700/50 rounded-lg p-4">
                <div className="text-gray-400 text-sm mb-1">{key}</div>
                <div className="text-gray-100">{value}</div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Guardant360 Reports */}
      <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div className="flex items-center gap-2 mb-6">
          <FileText className="w-5 h-5 text-blue-400" />
          <h2 className="text-xl font-semibold text-gray-100">Guardant360 Liquid Biopsy Tracking</h2>
        </div>

        <div className="space-y-6">
          {data.guardant360.map((report, reportIndex) => (
            <div key={reportIndex} className="bg-gray-700/50 rounded-lg p-4">
              <div className="flex justify-between items-center mb-4">
                <h3 className="font-medium text-gray-100">{report.reportDate}</h3>
                <div className="text-sm">
                  <span className="text-gray-400">ctDNA:</span>
                  <span className="text-gray-100 ml-2">{report.ctDNA}</span>
                </div>
              </div>

              <div className="space-y-4">
                {report.findings.map((finding, index) => (
                  <div key={index} className="grid grid-cols-5 gap-4 text-sm">
                    <div className="text-gray-100">{finding.gene}</div>
                    <div className="text-gray-400">{finding.alteration}</div>
                    <div className="text-gray-100">{finding.alleleFrequency}</div>
                    <div className="text-gray-400">{finding.previousFrequency}</div>
                    <div className={`${
                      finding.change.startsWith('+') 
                        ? 'text-red-400' 
                        : finding.change.startsWith('-')
                          ? 'text-green-400'
                          : 'text-gray-300'
                    }`}>
                      {finding.change}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Clinical Significance Alert */}
      <div className={`${
        patientId === 1
          ? 'bg-red-900/30 border-red-700'
          : 'bg-green-900/30 border-green-700'
      } border rounded-lg p-4 flex items-start`}>
        <AlertCircle className={`w-5 h-5 mr-3 mt-0.5 flex-shrink-0 ${
          patientId === 1 ? 'text-red-400' : 'text-green-400'
        }`} />
        <div>
          <h3 className={`font-medium mb-1 ${
            patientId === 1 ? 'text-red-300' : 'text-green-300'
          }`}>
            Molecular Insights
          </h3>
          <p className={patientId === 1 ? 'text-red-200' : 'text-green-200'}>
            {patientId === 1
              ? "Rising ctDNA levels and increasing allele frequencies indicate disease progression. MEN1 and TSC2 alterations suggest potential benefit from targeted therapy with everolimus."
              : "Decreasing ctDNA levels and reducing allele frequencies suggest good response to current therapy. KRAS G12D mutation indicates continued monitoring for resistance development."}
          </p>
        </div>
      </div>
    </div>
  );
};

export default MolecularView;
