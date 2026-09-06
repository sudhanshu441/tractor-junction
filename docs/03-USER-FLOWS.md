# Krishi Junction — User Flows & Process Diagrams

Every flow below is a build contract: the screens, states and side-effects named here are
what will be implemented.

- [0. High-level system flow](#0-high-level-system-flow)
- [1. Buyer — new tractor discovery → enquiry](#1-buyer--new-tractor-discovery--enquiry)
- [2. Buyer — used tractor purchase](#2-buyer--used-tractor-purchase)
- [3. Seller — list a used tractor](#3-seller--list-a-used-tractor)
- [4. Moderation — used listing approval](#4-moderation--used-listing-approval)
- [5. Inspection & verified badge](#5-inspection--verified-badge)
- [6. Lead capture → routing → closure](#6-lead-capture--routing--closure)
- [7. Loan application lifecycle](#7-loan-application-lifecycle)
- [8. EMI calculator](#8-emi-calculator)
- [9. Dealer onboarding & dealer panel](#9-dealer-onboarding--dealer-panel)
- [10. Authentication (OTP)](#10-authentication-otp)
- [11. Comparison flow](#11-comparison-flow)
- [12. Admin catalogue publishing](#12-admin-catalogue-publishing)
- [13. Review moderation](#13-review-moderation)
- [14. Notification dispatch](#14-notification-dispatch)
- [15. State machines](#15-state-machines)

---

## 0. High-level system flow

```mermaid
flowchart LR
    V((Visitor)) --> WEB[Website]
    WEB --> DISC[Discovery: catalogue, search, compare]
    WEB --> USED[Used marketplace]
    WEB --> FIN[Finance tools]
    WEB --> CONT[Content: news, videos, FAQ]

    DISC --> LEAD[Lead engine]
    USED --> LEAD
    FIN --> LOAN[Loan applications]
    CONT --> DISC

    LEAD --> ROUTE{Routing rules<br/>geo + brand + category}
    ROUTE --> DEALER[Dealer panel]
    ROUTE --> SALES[Sales executive]
    LOAN --> FINTEAM[Finance executive] --> LENDER[(Lender partners)]

    DEALER --> CLOSE[Contact → Qualify → Convert]
    SALES --> CLOSE
    CLOSE --> REPORT[Reports & analytics]

    ADMIN[Admin panel] -.manages.-> DISC
    ADMIN -.moderates.-> USED
    ADMIN -.verifies.-> DEALER
    ADMIN -.configures.-> LEAD
```

---

## 1. Buyer — new tractor discovery → enquiry

```mermaid
flowchart TD
    A[Landing: home / Google organic] --> B{Entry intent}
    B -->|Browse| C[Category or brand page]
    B -->|Search| D[Search results w/ suggestions]
    B -->|Budget| E[Price-list page: under ₹5L / 40-50 HP]

    C --> F[Listing page + filters<br/>brand, HP, price, WD, fuel]
    D --> F
    E --> F

    F --> G[Model detail page]
    G --> G1[Key specs + price + images]
    G --> G2[On-road price by city]
    G --> G3[EMI widget]
    G --> G4[Reviews & ratings]
    G --> G5[Dealers near you]
    G --> G6[Similar / competitor models]

    G6 --> H[Compare up to 4 models]
    H --> G

    G --> I{CTA chosen}
    I -->|Get best price| J[Enquiry form: name, mobile, city]
    I -->|Check EMI| K[EMI calculator prefilled]
    I -->|Contact dealer| L[Dealer enquiry form]
    I -->|Apply loan| M[Loan application]
    I -->|Save| N[Wishlist - requires login]

    J --> O[Send OTP to mobile]
    L --> O
    O --> P{OTP valid?}
    P -->|No, <5 attempts| O
    P -->|No, >=5| Q[Blocked 1 hour]
    P -->|Yes| R[Lead created<br/>type=new_product]
    R --> S[Auto-assign to dealer/sales]
    S --> T[SMS to buyer: 'we will call you']
    S --> U[Notification to dealer]
    R --> V[Thank-you page + related models + brochure download]
```

---

## 2. Buyer — used tractor purchase

```mermaid
flowchart TD
    A[/Used tractors page/] --> B[Filters: brand, model, year,<br/>price, HP, hours, district, verified]
    B --> C[Result cards: photo, year, hours,<br/>price, city, verified badge]
    C --> D[Used listing detail]
    D --> D1[Image gallery + condition report]
    D --> D2[Specs pulled from catalogue model]
    D --> D3[Inspection report if verified]
    D --> D4[Fair-price range indicator]
    D --> D5[Seller: type, city, member since]
    D --> D6[Similar used listings]

    D --> E{Action}
    E -->|Show contact| F[Mobile OTP verify]
    E -->|Request callback| F
    E -->|Get finance| G[Used-purchase loan form]
    E -->|Report listing| H[Report reason form]
    E -->|Save| I[Wishlist]

    F --> J{Verified?}
    J -->|Yes| K[Lead created type=used_listing]
    K --> L[Masked seller number revealed<br/>+ SMS to both parties]
    K --> M[Seller sees lead in panel]
    L --> N[Offline negotiation & inspection visit]
    N --> O{Deal done?}
    O -->|Yes| P[Seller marks listing SOLD<br/>+ optional feedback/review]
    O -->|No| Q[Lead marked lost with reason]
    H --> R[Moderation queue]
```

---

## 3. Seller — list a used tractor

Target: **under 3 minutes on a phone.**

```mermaid
flowchart TD
    A[Sell your tractor CTA] --> B[Step 1: Category<br/>tractor / implement / harvester]
    B --> C[Step 2: Brand → Model → Year<br/>model matched to catalogue]
    C --> D[Step 3: Usage & condition<br/>hours, tyres, RC, insurance, financed?]
    D --> E[Step 4: Photos<br/>min 4 guided angles, max 12]
    E --> F[Step 5: Price & location<br/>expected price, negotiable, city/pincode]
    F --> G[Step 6: Contact + OTP verify]

    G --> H{OTP verified?}
    H -->|No| G
    H -->|Yes| I[Duplicate check:<br/>same mobile + model in 24h?]
    I -->|Duplicate| J[Warn: you already have a live listing → edit instead]
    I -->|Unique| K[Listing saved status=pending]

    K --> L[Fair-price hint shown<br/>'similar tractors sell for ₹3.4–4.1L']
    K --> M[SMS: listing under review]
    K --> N[Moderation queue]
    K --> O{Opt-in to inspection?}
    O -->|Yes| P[Inspection requested]
    O -->|No| Q[Continue]

    N --> R[See flow 4]
```

Autosave keeps a `draft` at every step so an interrupted seller can resume from a link.

---

## 4. Moderation — used listing approval

```mermaid
flowchart TD
    A[Listing: PENDING] --> B[Moderator opens queue<br/>sorted oldest-first, SLA 6h]
    B --> C{Checks}
    C --> C1[Photos genuine & of the stated machine?]
    C --> C2[Price within sane band vs valuation?]
    C --> C3[No phone/URL in text or images?]
    C --> C4[Model/brand/year consistent?]
    C --> C5[Not a duplicate?]

    C --> D{Decision}
    D -->|Approve| E[status=live, published_at=now,<br/>expires_at=+60d, slug generated]
    D -->|Request changes| F[status=pending + remarks<br/>SMS+notification to seller]
    D -->|Reject| G[status=rejected + reason<br/>seller notified, can edit & resubmit]
    D -->|Block| H[status=blocked, user flagged]

    E --> I[Indexed in Meilisearch + sitemap]
    E --> J[Alert users with matching saved searches]
    F --> K[Seller edits → back to PENDING]
    G --> K

    E --> L{Day 53}
    L --> M[Reminder: renew?]
    M -->|Renewed| N[expires_at +60d]
    M -->|Ignored, day 60| O[status=expired, de-indexed]
```

---

## 5. Inspection & verified badge

```mermaid
flowchart TD
    A[Inspection requested<br/>by seller or admin] --> B[Admin schedules:<br/>inspector + date + slot]
    B --> C[SMS to seller with slot]
    C --> D[Inspector visits, fills checklist<br/>7 sections × items, score 0-10 + photos]
    D --> E[System computes weighted score → grade A/B/C/D]
    E --> F[Valuation range =<br/>base price × age × hours × condition × region]
    F --> G[Report PDF generated]
    G --> H{Admin approves report?}
    H -->|Yes| I[Listing is_verified=true<br/>badge + report link public]
    H -->|No| J[Back to inspector for correction]
    I --> K[Listing gets ranking boost in search]
```

---

## 6. Lead capture → routing → closure

```mermaid
flowchart TD
    A[Any enquiry form submitted] --> B[Validate + rate-limit + reCAPTCHA]
    B --> C[Mobile OTP verification]
    C --> D[Lead row created<br/>type, leadable, geo, utm, channel]
    D --> E{Duplicate?<br/>same mobile+leadable in 7 days}
    E -->|Yes| F[status=duplicate, linked to original,<br/>original gets an activity entry]
    E -->|No| G[Routing engine]

    G --> H{Lead type}
    H -->|new_product| I[Eligible dealers:<br/>district + brand + category + active plan + under daily cap]
    H -->|used_listing| J[Assign to seller/dealer who owns the listing]
    H -->|dealer| K[Assign to that dealer]
    H -->|loan/insurance| L[Assign to finance executive by state]
    H -->|contact/callback| M[Assign to support queue]

    I --> N{Any eligible dealer?}
    N -->|Yes| O[Round-robin pick + assignment row]
    N -->|No| P[Fallback: sales executive for that state]

    O --> Q[Notify: SMS + push + in-app<br/>Buyer gets 'dealer will call' SMS]
    Q --> R{Dealer responds within 2h?}
    R -->|No| S[Escalate: reassign to next dealer<br/>+ alert admin]
    R -->|Yes| T[status=contacted, activity logged]

    T --> U{Qualified?}
    U -->|No| V[status=lost + reason code]
    U -->|Yes| W[status=qualified, follow-up date set]
    W --> X{Purchased?}
    X -->|Yes| Y[status=converted, conversion value recorded]
    X -->|No| Z[Follow-up reminders until N attempts, then lost]

    Y --> AA[Feeds dealer & admin reports]
    V --> AA
```

---

## 7. Loan application lifecycle

```mermaid
flowchart TD
    A[Apply for loan CTA<br/>from model / used listing / loan page] --> B[Eligibility pre-check<br/>age, income, land, machinery price]
    B --> C{Eligible?}
    C -->|No| D[Show reason + alternate options + capture as lead]
    C -->|Yes| E[Step 1: Applicant & contact + OTP]
    E --> F[Step 2: Machinery & loan need<br/>price, down payment, tenure → live EMI]
    F --> G[Step 3: Income & land details]
    G --> H[Step 4: Documents upload<br/>Aadhaar, PAN, land record, bank statement]
    H --> I[Submit → status=submitted, ref no issued]

    I --> J[Finance executive assigned by state]
    J --> K{Documents complete & legible?}
    K -->|No| L[status=docs_pending<br/>SMS + email with the missing list]
    L --> H
    K -->|Yes| M[status=under_review → verify + CIBIL check]
    M --> N[Select lenders by amount/tenure/state/LTV]
    N --> O[status=sent_to_lender<br/>row per lender in loan_application_lenders]
    O --> P{Lender decision}
    P -->|Sanctioned| Q[status=sanctioned, amount + rate recorded]
    P -->|Rejected| R[Try next lender / status=rejected with reason]
    Q --> S[status=disbursed after payout confirmation]
    S --> T[Commission recorded, applicant notified, dealer informed]

    I -.-> U[Applicant tracks status in customer panel]
    L -.-> U
    Q -.-> U
```

---

## 8. EMI calculator

```mermaid
flowchart LR
    A[Inputs] --> A1[Tractor price - prefilled from model]
    A --> A2[Down payment - slider, default 20%]
    A --> A3[Interest rate - default 11.5%]
    A --> A4[Tenure 12-84 months]
    A --> A5[Repayment: monthly/quarterly/half-yearly/yearly]
    A1 & A2 & A3 & A4 & A5 --> B["EMI = P·r·(1+r)^n / ((1+r)^n − 1)"]
    B --> C[Outputs: EMI, total interest,<br/>total payable, donut chart]
    C --> D[Amortisation schedule table]
    C --> E[CTA: Apply for this loan → flow 7]
    C --> F[Share URL / WhatsApp with params]
    C --> G[Log to emi_calculations for analytics]
```

---

## 9. Dealer onboarding & dealer panel

```mermaid
flowchart TD
    A[Become a dealer / partner page] --> B[Application form<br/>business, GSTIN, brands, city, contact]
    B --> C[OTP verify + submit → verification_status=pending]
    C --> D[Admin review queue]
    D --> E{Documents valid?}
    E -->|No| F[Request documents / reject with reason]
    E -->|Yes| G[verified, dealer code issued,<br/>owner user created + credentials SMS/email]
    G --> H[Dealer logs into dealer panel]

    H --> I[Dashboard: leads today, views, plan usage]
    H --> J[Inventory: add new-stock products + prices,<br/>post used listings]
    H --> K[Leads inbox: accept, call, note, status]
    H --> L[Branches & staff users]
    H --> M[Profile, photos, working hours, documents]
    H --> N[Reviews received → reply]
    H --> O[Subscription & plan → upgrade]
    H --> P[Reports: leads, conversions, listing performance]

    K --> Q{Lead handled in SLA?}
    Q -->|No| R[Auto-reassign + dealer score penalty]
    Q -->|Yes| S[Dealer performance score ↑<br/>affects routing priority]
```

---

## 10. Authentication (OTP)

```mermaid
sequenceDiagram
    autonumber
    participant U as User
    participant W as Web/App
    participant API as Laravel
    participant SMS as SMS gateway
    participant DB as MySQL/Redis

    U->>W: Enters 10-digit mobile
    W->>API: POST /auth/otp/send
    API->>API: Validate format, rate-limit (5/hr/mobile, 20/hr/IP)
    API->>DB: Store otp_hash + expires_at (10 min)
    API->>SMS: Send 6-digit OTP
    SMS-->>U: SMS delivered
    U->>W: Enters OTP
    W->>API: POST /auth/otp/verify
    API->>DB: Compare hash, check expiry & attempts
    alt Valid
        API->>DB: Find or create user, set mobile_verified_at
        API-->>W: Session (web) or Sanctum token (app)
        W-->>U: Redirect to intended action
    else Invalid
        API->>DB: attempts++
        API-->>W: Error; block after 5 attempts for 1 hour
    end
```

---

## 11. Comparison flow

```mermaid
flowchart TD
    A[Add to compare from<br/>listing card / detail / compare page] --> B[Stored in session + DB if logged in]
    B --> C{Count}
    C -->|1| D[Floating compare bar: 'add 1 more']
    C -->|2-4| E[Compare button active]
    E --> F["/compare/mahindra-575-di-vs-swaraj-744-fe"]
    F --> G[Table: price, key specs by group,<br/>differences highlighted, sticky headers]
    G --> H[Per-column CTAs: enquiry, EMI, dealers]
    G --> I[Swap/remove a model]
    G --> J[Share URL / download PDF]
    G --> K[SEO: indexable page + FAQ + verdict text]
```

---

## 12. Admin catalogue publishing

```mermaid
flowchart TD
    A[Catalog manager: New product] --> B[Basics: brand, category, name, HP, status]
    B --> C[Variants]
    C --> D[Specs — form auto-built from<br/>category_spec_attribute mapping]
    D --> E[Prices: national + state rows, effective dates]
    E --> F[Media: images with alt, videos, brochure]
    F --> G[Features, FAQs, competitors]
    G --> H[SEO meta - auto-templated, editable]
    H --> I{Publish?}
    I -->|Draft| J[Not visible on site]
    I -->|Publish| K[is_active=true]
    K --> L[Cache invalidated, search re-indexed,<br/>sitemap regenerated, price history written]
    L --> M[Activity log entry]
```

---

## 13. Review moderation

```mermaid
flowchart TD
    A[Logged-in user writes review] --> B[Profanity + spam filter]
    B --> C{Auto-flagged?}
    C -->|Yes| D[status=pending, priority queue]
    C -->|No| D
    D --> E[Moderator: approve / reject with reason]
    E -->|Approved| F[Visible; aggregates recomputed on the model/dealer]
    E -->|Rejected| G[Author notified with reason]
    F --> H[Other users vote helpful]
    F --> I[Dealer/brand may reply once]
```

---

## 14. Notification dispatch

```mermaid
flowchart LR
    A[Domain event<br/>lead.created, listing.approved, loan.status_changed] --> B[Laravel event listener]
    B --> C[Load notification_template by event_key]
    C --> D{Channel enabled + user opted in?}
    D -->|SMS| E[Queue: SmsJob → MSG91]
    D -->|Email| F[Queue: MailJob → SES/SMTP]
    D -->|Push| G[Queue: FcmJob]
    D -->|WhatsApp| H[Queue: WaJob → BSP template]
    D -->|In-app| I[notifications table]
    E & F & G & H --> J[notification_logs: status, error, sent_at]
    J --> K{Failed?}
    K -->|Yes| L[Retry ×3 backoff → alert admin after final failure]
```

---

## 15. State machines

### Used listing
```mermaid
stateDiagram-v2
    [*] --> draft
    draft --> pending: submit
    pending --> live: approve
    pending --> rejected: reject
    rejected --> pending: edit & resubmit
    live --> sold: seller marks sold
    live --> expired: 60 days
    expired --> pending: renew
    live --> blocked: policy violation
    blocked --> [*]
    sold --> [*]
```

### Lead
```mermaid
stateDiagram-v2
    [*] --> new
    new --> assigned: routing engine
    new --> duplicate: dedupe
    new --> invalid: bad number
    assigned --> contacted: first call
    assigned --> assigned: re-assign on SLA breach
    contacted --> qualified
    contacted --> lost
    qualified --> converted
    qualified --> lost
    converted --> [*]
    lost --> [*]
```

### Loan application
```mermaid
stateDiagram-v2
    [*] --> draft
    draft --> submitted
    submitted --> under_review
    under_review --> docs_pending
    docs_pending --> under_review: docs uploaded
    under_review --> sent_to_lender
    sent_to_lender --> sanctioned
    sent_to_lender --> rejected
    sanctioned --> disbursed
    rejected --> [*]
    disbursed --> [*]
    submitted --> cancelled: applicant withdraws
```

### Dealer
```mermaid
stateDiagram-v2
    [*] --> pending
    pending --> verified: docs approved
    pending --> rejected
    verified --> suspended: policy / non-payment
    suspended --> verified: resolved
    rejected --> pending: reapply
```
