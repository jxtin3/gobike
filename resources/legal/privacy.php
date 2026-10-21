<?php

/*
 * Default Privacy Policy. Published to the legal_documents table by migration and used as the
 * fallback when no database copy exists. After that, edit it in Admin → Legal.
 *
 * Formatting: blank line = new paragraph, "- " = bullet, **bold**, [text](/path or https://…).
 * Bare URLs and email addresses become links automatically.
 */

return [
    'title' => 'Privacy Policy',
    'last_updated' => 'October 22, 2026',
    'sections' => [
        [
            'title' => '1. Who We Are',
            'content' => <<<'TXT'
This Privacy Policy explains how **Padyarescue Incorporated** ("PRI", "we", "us" or "our"), the organization behind the Go Bike Project, collects, uses, shares and protects personal information when you:

- visit this website (the "Site");
- contact us, apply as a volunteer, request a training program or make a donation through the Site;
- chat with GoBot, our website assistant; or
- use the Go Bike mobile app as a resident, user or GoBiker volunteer (the "App").

PRI is the personal information controller for the information described in this policy. We handle personal information in line with the **Data Privacy Act of 2012 (Republic Act No. 10173)**, its Implementing Rules and Regulations, and the issuances of the National Privacy Commission (NPC).

By using the Site or App, you acknowledge that you have read this policy. Where we rely on your consent, we will ask for it clearly, for example through a checkbox on our forms.
TXT,
        ],
        [
            'title' => '2. Information We Collect',
            'content' => <<<'TXT'
**Information you give us**

- **Contact form:** your name, email address, phone number (optional), subject and message.
- **Volunteer applications:** your name, email address, mobile number, age, occupation or school status, school, barangay, municipality and province, availability, whether you have a bicycle, why you want to volunteer, and the name and number of an emergency contact. If you are 13 to 17 years old, we also collect your parent's or guardian's name, contact number and consent.
- **Training requests:** the name, email address, phone number and address of the person or organization making the request, the training package selected, and your message.
- **Chapter replication inquiries:** your organization's name and type, the contact person's name, position, email address and mobile number, your municipality or city and province, the number of trainees, your preferred training date, your message, and the status of any training payment.
- **Donations:** your name (and organization name, if you give on behalf of one), email address, phone number (optional), billing address, country, donation amount, frequency (one-time or monthly) and payment method.
- **App accounts:** your name, email address, mobile number, barangay, account type (resident, user or GoBiker) and password, which we store only in a securely hashed form. If you sign in with Google, we receive your Google account ID, name and email address from Google.
- **Messages to our team:** anything you send us through the App.

**Information collected when GoBikers use the App**

- **Location:** while a GoBiker has an active duty session, or sends an emergency alert, the App shares the device's current location with our response coordinators. We keep only the most recent location, not a history of movements, and sharing stops when the session ends.
- **Patient records:** when a trained GoBiker provides basic health monitoring (for example, during Ronda Kalusugan visits), the GoBiker records the person's name, address, contact number, age, barangay and health readings such as blood pressure, pulse, respiratory rate, temperature, height and weight.

**Information collected automatically**

- **Technical data:** your IP address, browser and device type, the pages you request, and the date and time of access. This is recorded in our server logs and session records to keep the Site working and secure.
- **Cookies:** see our [Cookie Policy](/cookie-policy).

**GoBot chat messages**

What you type into GoBot is sent to Google's Gemini service to generate a reply. We do not save GoBot conversations in our database. The conversation stays in your browser only until you leave or reload the page.
TXT,
        ],
        [
            'title' => '3. Sensitive Personal Information',
            'content' => <<<'TXT'
Some of the information we handle is **sensitive personal information** under the Data Privacy Act, including a person's age, health readings taken during health monitoring, and information about minors. We process sensitive personal information only:

- with the consent of the person concerned, or of their parent or guardian in the case of a minor;
- when necessary to protect the life and health of a person who is unable to give consent, such as during an emergency response; or
- as otherwise allowed or required by law.

Access to patient records is limited to the GoBiker who recorded them and to authorized PRI personnel who coordinate health services.
TXT,
        ],
        [
            'title' => '4. How We Use Your Information',
            'content' => <<<'TXT'
We use personal information only for the purposes it was collected for, namely to:

- respond to your inquiries and messages;
- review volunteer applications, check eligibility (such as age and guardian consent), contact applicants, and organize trainings, schedules and assignments;
- coordinate field operations, dispatch help, monitor the safety of GoBikers on duty and respond to emergency alerts;
- provide basic health monitoring and refer people to health facilities when needed;
- process donations and training payments, manage monthly donations, prevent fraud and keep the accounting and tax records required by law;
- handle training and chapter replication requests, prepare proposals and coordinate with partner institutions;
- operate, secure and improve the Site and App, including preventing spam, abuse and unauthorized access;
- prepare statistics and reports about our programs, such as the number of people served per barangay. Reports we publish or share with partners use aggregated or anonymized data that does not identify anyone; and
- comply with our legal obligations and with lawful requests from authorities.

**We do not sell your personal information, and we do not use it for advertising.**
TXT,
        ],
        [
            'title' => '5. Our Legal Basis for Processing',
            'content' => <<<'TXT'
We process personal information only when the Data Privacy Act allows it, in particular:

- **Consent:** for example, volunteer applications, health monitoring and optional cookies. You may withdraw consent at any time (see Section 10).
- **Contract:** to set up and manage App accounts, process donations, and respond to training requests you send us.
- **Legal obligation:** to keep tax and accounting records and to comply with lawful orders.
- **Protection of life and health:** to respond to emergencies and protect people who cannot give consent at that moment.
- **Legitimate interests:** to secure our systems, prevent fraud and abuse, and improve our services, provided these interests do not override your fundamental rights and freedoms.
TXT,
        ],
        [
            'title' => '6. How We Share Information',
            'content' => <<<'TXT'
We share personal information only when necessary, and never more than is needed.

**Service providers** that process data on our behalf, under appropriate safeguards:

- **Laravel Cloud** and its infrastructure partners host the Site, the App's server, our database and file storage.
- **Google** provides GoBot replies (Gemini), App sign-in (Google Sign-In) and the map on our Contact page (Google Maps, loaded only if you allow it).
- **PayPal and PayMongo** process card, PayPal, GCash and online banking payments. We never see or store your full card or e-wallet details; the payment provider handles them under its own privacy policy.

We require our service providers to protect personal information and to use it only to provide their services to us.

**Other organizations**, only when needed:

- **Partner institutions**, such as barangay and municipal offices, disaster risk reduction and management offices, schools and health centers, to coordinate trainings, emergency response, referrals or program activities. Each partner receives only the information it needs.
- **Emergency and health services**, when necessary to protect someone's life or health.
- **Government authorities**, when required by law, regulation, court order or a lawful request.
- **A successor organization**, if PRI's programs are transferred to or merged with another organization, which must continue to protect your information as described in this policy.
TXT,
        ],
        [
            'title' => '7. International Data Transfers',
            'content' => <<<'TXT'
Some of our service providers, including our hosting provider, Google and PayPal, may store or process information on servers located outside the Philippines. When this happens, we remain responsible for your information and take steps to make sure it stays protected as required by the Data Privacy Act, including working only with reputable providers that maintain appropriate security measures.
TXT,
        ],
        [
            'title' => '8. How Long We Keep Information',
            'content' => <<<'TXT'
We keep personal information only for as long as it is needed for the purposes above, or as long as the law requires. Our general retention periods are:

- **Contact messages:** up to 2 years after our last reply, unless needed for an ongoing matter.
- **Volunteer applications:** while you are an active volunteer and up to 3 years after you leave the program. Applications that are not accepted are deleted within 1 year.
- **Training requests and chapter inquiries:** up to 3 years after the request is closed, or longer if covered by a written agreement.
- **Donation and payment records:** for the period required by Philippine tax, accounting and audit rules.
- **App accounts:** while your account is active. When you ask us to close your account, we delete or anonymize your data within 90 days, except records we must keep by law.
- **Patient records:** for as long as needed for continuity of care and program reporting, after which they are securely deleted or anonymized, unless the law requires a longer period.
- **Location data:** only the most recent location is kept, and it is replaced with every update.
- **Server logs and session records:** for a limited time, generally a few weeks, to keep the Site secure.

When information is no longer needed, we securely delete it or anonymize it so that it no longer identifies anyone.
TXT,
        ],
        [
            'title' => '9. How We Protect Your Information',
            'content' => <<<'TXT'
We use reasonable organizational, physical and technical measures to protect personal information, including:

- encrypted connections (HTTPS) for the Site and App;
- passwords stored using strong one-way hashing, never in plain text;
- access controls, so that only authorized and approved personnel can view personal information, and only what they need for their role;
- protection against common attacks, such as rate limiting, input validation and security headers;
- payment processing by PCI DSS-compliant providers, so card and e-wallet details never pass through our systems; and
- confidentiality obligations for the staff and volunteers who handle personal information.

No system is completely secure. If a personal data breach occurs that is likely to put you at risk of serious harm, we will notify you and the National Privacy Commission within the period required by law (generally within 72 hours of becoming aware of it).
TXT,
        ],
        [
            'title' => '10. Your Rights',
            'content' => <<<'TXT'
Under the Data Privacy Act, you have the right to:

- **Be informed** whether and how your personal information is being processed.
- **Access** your personal information and receive a copy of it.
- **Object** to processing, including withdrawing consent you have given.
- **Correct** inaccurate or incomplete information.
- **Erasure or blocking** of information that is no longer necessary, was unlawfully obtained, or is being processed without a valid basis.
- **Data portability**, meaning to receive your information in a commonly used electronic format, where applicable.
- **Damages** if you are harmed by inaccurate, incomplete, outdated, false, unlawfully obtained or unauthorized use of your personal information.
- **File a complaint** with the National Privacy Commission.

To use any of these rights, email our Data Protection Officer at gobike@pydn.org. We may ask you to verify your identity first, to protect your information from people pretending to be you. We aim to respond within 15 working days.

Withdrawing consent does not affect processing we carried out before you withdrew it, and we may need to keep some information to meet our legal obligations.
TXT,
        ],
        [
            'title' => '11. Children and Minors',
            'content' => <<<'TXT'
The Site and App are not directed to children under 13, and we do not knowingly collect their personal information. If you believe a child under 13 has given us personal information, please contact us and we will delete it.

Volunteers must be 13 to 25 years old. Applicants aged 13 to 17 need the consent of a parent or guardian, and we collect the parent's or guardian's name and contact number for this purpose.

Health monitoring of minors is done only with the consent of a parent or guardian, except in an emergency.
TXT,
        ],
        [
            'title' => '12. Photos and Videos',
            'content' => <<<'TXT'
We take photos and videos at public activities, trainings and community events, and may publish them on the Site, in our gallery, on social media and in reports. We avoid publishing images that reveal sensitive information, such as a person's health condition.

If you appear in our photos or videos and would like them removed, email gobike@pydn.org. We will remove them from the Site and our social media pages within a reasonable time.
TXT,
        ],
        [
            'title' => '13. Third-Party Websites and Services',
            'content' => <<<'TXT'
The Site and App link to, or use, websites and services run by others, such as social media pages, news articles, Google, PayPal and PayMongo. We are not responsible for their privacy practices, so please read their privacy policies before giving them your information.
TXT,
        ],
        [
            'title' => '14. Changes to This Policy',
            'content' => <<<'TXT'
We may update this Privacy Policy from time to time, for example when we add new services or when the law changes. The "Last updated" date at the top of this page shows when it was last revised. If we make significant changes, we will post a notice on the Site or contact you where appropriate.
TXT,
        ],
        [
            'title' => '15. Contact Our Data Protection Officer',
            'content' => <<<'TXT'
If you have questions about this policy, or want to use your rights, please contact:

**Data Protection Officer**
Padyarescue Incorporated – Go Bike Project
Bugallon, Pangasinan, Philippines
Email: gobike@pydn.org
Phone: +63 976 429 0145

If you are not satisfied with our response, you may file a complaint with the National Privacy Commission at https://privacy.gov.ph.
TXT,
        ],
    ],
];
