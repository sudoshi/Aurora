# Aurora Competitive Analysis: Tumor Board Platforms

## 1. navify® Clinical Hub for Tumor Boards (Roche)

### Overview
Originally launched as navify Tumor Board, the platform was recently rebranded to navify Clinical Hub (nCH) with enhanced features including an optimized UI, integrated clinical data sources, and AI-powered analytics. It's backed by a partnership with GE Healthcare for medical imaging integration.

### Scale & Market Position
More than 6,500 customers trust navify digital solutions from Roche overall. For the tumor board product specifically, Roche reports 70+ customers eligible for KLAS research, spanning US and non-US organizations. This is by far the most enterprise-embedded of the competitors, benefiting from Roche's massive diagnostics sales force and existing lab relationships.

### Validated Outcomes (Ellis Fischel Study)
At the University of Missouri's Ellis Fischel Cancer Center, implementing navify across four tumor boards achieved a **per-case discussion cost reduction of 40–52%**. Before navify, residents spent up to six hours a week preparing for each conference using disparate sources and PowerPoint presentations. The platform integrated with their Cerner EHR to automate preparation. Multiple peer-reviewed publications by Hammer et al. (2020, 2021) document these results in *JCO Clinical Cancer Informatics* and *Health and Technology*.

### KLAS Performance
navify Tumor Board attained an overall performance score of **92.4**, well above the 2023 Best in KLAS global healthcare software average of 80.3. Customers gave an A+ score for willingness to recommend.

### Key Technical Capabilities
- Real-time NCCN guideline integration with "Smart Navigation" that auto-opens the most relevant guideline section based on patient data
- Searches across over 21 trial registries in one place and matches patients to relevant clinical trials, including prioritization of trials within the user's institution
- Searches 858,000+ publications from PubMed, ASCO, ESMO, and AACR
- NLP technology to structure genomic and pathology reports

### Integration Model
- EHR integration (documented with Cerner/Oracle Health at Ellis Fischel; also deployed at Hospital del Mar Barcelona)
- GE Healthcare imaging viewer built in
- Cloud-based, HIPAA and GDPR compliant
- Supports customizable institutional guidelines through a configuration module

### Strengths vs. Aurora
Roche's unmatched distribution infrastructure, peer-reviewed clinical evidence base, KLAS validation, and the GE Healthcare imaging partnership. The breadth of the navify ecosystem (lab operations, POC, analytics) creates stickiness.

### Vulnerabilities
This is a proprietary, enterprise SaaS product from one of the world's largest diagnostics companies — the antithesis of open source. Pricing is opaque and likely substantial. The 70+ customer count for the tumor board module specifically suggests slower enterprise adoption than the marketing might imply. The platform is tightly coupled to Roche's broader diagnostics strategy, which may create conflicts of interest (e.g., favoring Roche assays in the genomic decision support layer).

---

## 2. OncoLens

### Overview
Founded in 2016 and headquartered in Atlanta, Georgia, OncoLens is a HIPAA-compliant web and mobile platform for connecting oncologists. Led by CEO and Co-Founder Anju Mathew.

### Funding & Growth
OncoLens raised **$16 million in a Series B round in October 2024**, co-led by BIP Capital and Cross Border Impact Ventures, bringing total funding to $27.1M over 4 rounds. The Series B came on the heels of the company ranking No. 1296 on Inc 5000's 2024 list of fastest growing privately held companies.

### Scale
OncoLens now serves **more than 225 cancer centers** in the US and internationally, having launched in EMEA in 2022. The team is small — approximately 35 employees as of late 2023. This is a genuinely mission-driven startup, not an enterprise behemoth.

### Platform Architecture (7 Modules)
- Tumor board/conference management
- Analytics and reporting
- Patient tracking/ID
- Clinical trial matching
- Cancer registry integration (OncoLog and CRStar compatible with NAACCR reports)
- DICOM image sharing
- SSO authentication

OncoLens integration customers can reduce case finding time by an average of 40%, and more than 80% of new cancer cases imported into CRStar were "ready to abstract."

### AI & Data Strategy
This is where OncoLens is making its most aggressive play. The company's proprietary AI capabilities enable cancer centers to extract key insights from structured or unstructured clinical, molecular, and lab data to find patients who might have missed treatment-defining diagnostics or therapies, in real time. They use large language models tailored with proprietary oncology knowledge models, going deep into understanding lines of therapy, progression, and contextual patient information.

### Life Sciences Revenue Stream
OncoLens has built a dual-sided business model through the **OncoLens Research Network (ORN)**, partnering with life science commercial, real-world evidence, and clinical development teams to bring cutting-edge research and trials to their cancer centers. This is a strategic moat — pharma companies pay to access OncoLens's network of community and academic centers for trial feasibility, patient screening, and real-world evidence generation.

### Notable Customers/Partners
- Karmanos Cancer Institute (NCI-designated)
- UK Markey Cancer Center
- Ascension Lourdes
- The Sarcoma Alliance for Research Through Collaboration (SARC) for a nationwide virtual sarcoma tumor board
- Ohio State for affiliate clinical trial identification

OncoLens recently expanded its board, adding Dr. Prasanth Reddy as a director and Drs. Joseph Kim and Walter Curran as advisory board members.

### Strengths vs. Aurora
OncoLens is the most direct competitor to Aurora's vision of cross-enterprise clinical collaboration. Their community cancer center focus, life sciences revenue model, and network effects are compelling. The platform's ability to support asynchronous case review (not just synchronous meetings) is a genuine differentiator. Their AI-driven patient identification for missed biomarker testing and trial eligibility is clinically meaningful.

### Vulnerabilities
35 employees serving 225+ centers implies thin engineering resources. The platform is proprietary SaaS. No peer-reviewed publications documenting outcomes (unlike navify). No KLAS coverage yet. The life sciences revenue model, while strategically smart, could create subtle conflicts — is the platform optimizing for patient outcomes or for pharma partner access to patient populations?

---

## 3. GenomOncology Molecular Tumor Board

### Overview
Cleveland-based precision medicine software company, providing an end-to-end platform spanning pathology workbench, molecular tumor board, clinical decision support, and analytics. Led by CEO Brad Wertz and CTO Ian Maurer.

### Platform Architecture
GenomOncology operates at a fundamentally different layer than navify or OncoLens. Their **Precision Oncology Platform (POP)** is the backbone — a knowledge management system that aggregates and curates genomic research, clinical trials, and treatment guidelines.

- **Molecular Tumor Board module**: Case creation, variant review, clinical trial investigation, and clinical data review in a single workflow
- **GO Pathology Workbench**: Tertiary analysis of NGS data, automating somatic and germline variant interpretation
- **GenomAnalytics**: Visualization and statistical analysis across molecular, clinical, demographic, and treatment data

### Key Technical Differentiators
- Proprietary knowledgebases with curated ontologies spanning alterations, drugs, diseases, anatomic sites, genes, and pathways
- Direct integration with multiple NGS vendors and lab information systems
- Custom clinical genomic report generation
- A Precision Oncology API Suite that enables clinicians, researchers, and collaborative teams to extend the platform's knowledge capabilities

### BioMCP Initiative (April 2025)
This is the most strategically interesting recent move. GenomOncology announced **BioMCP**, a new open-source technology built on Anthropic's Model Context Protocol that helps AI systems access specialized medical information including clinical trials, genetic data, and published medical research.

While BioMCP is freely available as open-source software, GenomOncology is developing a commercial version (**OncoMCP**) for organizations that need enhanced security, on-premise deployment, and integration with clinical and research data. The commercial OncoMCP layer includes:
- HIPAA-compliant deployment
- Real-time trial matching
- EHR connectivity
- Curated knowledge base of 15,000+ trials and FDA approvals

### Recent Partnerships (2025)
GenomOncology has been aggressively signing partnerships:
- Glioblastoma Foundation for genomic testing integration
- Chronetyx Laboratories for reduced NGS turnaround times
- Belay Diagnostics for their Summit test
- Pillar Biosciences for NGS panel co-marketing
- Precipio for myeloid testing
- Earlier: Duke Cancer Institute for their molecular tumor board

### Strengths vs. Aurora
GenomOncology occupies the deepest molecular/genomic niche. Their BioMCP open-source strategy is philosophically aligned with Acumenus's approach, though in a very different domain. The platform is the natural choice when the primary use case is interpreting NGS results and making molecular-driven treatment decisions. Strong lab/pathology integration that other platforms lack.

### Vulnerabilities
GenomOncology is not really a general-purpose tumor board platform. It's focused on molecular tumor boards specifically — the intersection of genomics and clinical decision-making. It doesn't address the operational workflow challenges (meeting management, PACS integration, general case preparation) that navify and OncoLens tackle. This is a complementary technology, not a direct substitute for Aurora's broader clinical collaboration vision. Company size appears modest (no public employee counts, private company).

---

## 4. Caris Molecular Tumor Board™ (CMTB)

### Overview
The CMTB is an on-demand platform where clinicians, pathologists and scientists interact with leading cancer experts across the country, providing therapeutic guidance for difficult-to-treat cases. This is fundamentally different from the other three — **it's a service, not a software platform**.

### Parent Company Scale
- **$812M revenue** in 2025, up 97% year-over-year
- Public on NASDAQ (ticker: CAI)
- Market cap of approximately **$7.7 billion**
- Multimodal database now contains more than **740,000 matched patient records** of combined molecular and clinical outcomes data

### Precision Oncology Alliance
The Caris POA has expanded to **99 cancer centers**, including 45 NCI-designated centers. Members include:
- Mass General
- Columbia
- UVA
- UAMS
- Providence Swedish

This network provides Caris with massive data assets and creates deep institutional relationships.

### How the CMTB Works
- Cases must have Caris molecular profiling
- Reviews happen via two channels:
  - Virtual (asynchronous) molecular tumor board
  - Live monthly calls where specialists discuss 3-4 cases selected for their unique molecular and clinicopathological features
- Board members include leading oncologists from Fox Chase, NCI, Montefiore, City of Hope, Washington University/Siteman, and other major centers

Caris recently launched the **Molecular Tumor Board Report**, an AI-enhanced RUO profiling report that presents molecular data in modular, easy-to-interpret formats.

### AI Capabilities
- Proprietary AI-driven breast cancer signature for capecitabine, using more than 2,000 expression and copy-number features from WES and WTS
- **MI Cancer Seek assay** received FDA approval in November 2024 as a WES/WTS-based tissue assay with companion diagnostic indications — making Caris one of the few companies with FDA-cleared comprehensive molecular profiling

### Strengths vs. Aurora
The CMTB is less a software competitor and more a service competitor — it offers access to national experts that individual cancer centers can't assemble internally. The 99-center POA network, the 740K+ patient database, and Caris's AI/ML capabilities built on that database are formidable barriers. The Genentech collaboration (up to $1.1B in potential milestones) signals pharma validation.

### Vulnerabilities
The CMTB is entirely dependent on Caris molecular profiling — you can't submit cases without Caris assays. This creates a vendor lock-in that limits broad adoption. It's fundamentally a consultation service and report product, not a workflow platform for managing day-to-day tumor board operations. Community oncologists who use Foundation Medicine or Tempus for profiling are excluded. The "software" component is more of a portal than a platform.

---

## Strategic Implications for Aurora

The competitive landscape reveals distinct niches rather than a single crowded space:

| Platform | Primary Niche |
|----------|---------------|
| **navify** | Enterprise workflow/meeting management tier with Roche's distribution muscle |
| **OncoLens** | Cross-enterprise collaboration and life sciences network tier |
| **GenomOncology** | Molecular interpretation and precision oncology decision support tier |
| **Caris CMTB** | Expert consultation service tier |

### Aurora's Differentiated Position

**None of these are open source.** None run on OMOP/OHDSI standards. None integrate with the broader open research data ecosystem the way Aurora could via Parthenon and OHDSI community alignment.

Aurora's positioning as an **open-source clinical collaboration platform** — vendor-agnostic, standards-based, and community-governed — occupies genuinely uncontested space.

The key question is whether that open-source model can deliver the polish and clinical validation that enterprise buyers (and KLAS evaluators) demand.

### Market Outlook

The molecular tumor board market alone is projected to grow from **$1.34B (2024) to $2.53B by 2029** at 13.5% CAGR, indicating strong tailwinds for any platform in this space.
