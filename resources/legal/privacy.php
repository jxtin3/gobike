<?php

/*
 * Default Privacy Policy. Published to the legal_documents table by migration and used as the
 * fallback when no database copy exists. After that, edit it in Admin → Legal.
 *
 * Keep it true to what the Site and the RondaGo app actually do: when a feature starts or stops
 * collecting or sharing personal information, update this text (and publish it with a migration).
 *
 * Formatting: blank line = new paragraph, "- " = bullet, **bold**, [text](/path or https://…).
 * Bare URLs and email addresses become links automatically.
 */

return [
    'title' => 'Privacy Policy',
    'last_updated' => 'November 13, 2026',
    'sections' => [
        [
            'title' => '1. Our Privacy Commitment',
            'content' => <<<'TXT'
**Padyarescue Incorporated** ("PRI", "we", "us" or "our") is the organization behind the Go Bike Project. It was registered with the Securities and Exchange Commission on March 25, 2021, and is based in Bugallon, Pangasinan, Philippines.

We respect your privacy and are committed to protecting the personal information you entrust to us. This Privacy Policy explains what personal information we collect, why we collect it, how we use, share, protect and dispose of it, and what rights you have. It applies to:

- the Go Bike Project website at https://gobikeproject.org (the "Site"), including GoBot, our website assistant;
- the RondaGo mobile app used by residents and GoBiker volunteers (the "App"); and
- our forms, programs, trainings, field activities and community health monitoring.

PRI is the **personal information controller** for the information described in this policy. We process personal information in accordance with the **Data Privacy Act of 2012 (Republic Act No. 10173)**, its Implementing Rules and Regulations, and the issuances of the National Privacy Commission (NPC), and we follow the general privacy principles of transparency, legitimate purpose and proportionality.

By using the Site or App, you acknowledge that you have read this policy. Where we rely on your consent, we ask for it clearly before we collect the information, for example through a checkbox on our forms.
TXT,
        ],
        [
            'title' => '2. Definitions',
            'content' => <<<'TXT'
In this policy, the following terms have the meaning given to them by Section 3 of the Data Privacy Act:

- **Personal information** is any information from which a person's identity is apparent or can be reasonably and directly ascertained, or which, when put together with other information, would directly and certainly identify a person.
- **Sensitive personal information** is personal information about a person's age, health, education, government-issued identifiers and the other matters listed in Section 3(l) of the Data Privacy Act.
- **Processing** is any operation performed on personal information, including its collection, recording, organization, storage, updating, retrieval, use, sharing, blocking, erasure or destruction.
- **Data subject** is the person whose personal information is processed. In this policy, that is "you".
- **Personal information controller** is the organization that decides what personal information is collected and how it is used. For the Site and App, that is PRI.
- **Personal information processor** is an organization we engage to process personal information on our behalf, such as our hosting provider.
TXT,
        ],
        [
            'title' => '3. Personal Information We Collect',
            'content' => <<<'TXT'
We collect only the personal information we need for the purposes in Section 6.

**Through the Site's forms**

- **Contact form:** your name, email address, phone number (optional), subject and message.
- **Volunteer applications:** your first and last name, email address, mobile number, age, occupation or school status, school (optional), barangay, municipality and province, availability, your reasons for volunteering (optional), the name and number of an emergency contact, the type of ID you submit, and copies of the ID or certificate you upload, such as a school ID, a valid ID, a barangay clearance, or a certificate of indigency or residency.
- **Home training requests:** your name, email address, phone number and address, the training package you choose, your preferred date and time of day, copies of the ID you upload, and your message.
- **Chapter replication inquiries:** your organization's name and type, the contact person's name, position, email address and mobile number, your municipality or city and province, the number of trainees, your preferred training date, your message, and the payment option and payment status.
- **Donations:** online donations are not available at the moment. When they are, we will collect the donor's name (and organization name, if giving on behalf of one), email address, phone number (optional), billing address, country, donation amount, currency, frequency (one-time or monthly) and payment method.

**Through the RondaGo app**

- **Account details:** your name, email address, mobile number, barangay, birthdate, profile photo, account type (resident or GoBiker) and password, which we store only in hashed form. If you sign in with Google, we also receive your Google account ID, name and email address from Google.
- **GoBiker activity:** the start time, end time and barangay of each ronda (duty round), your location as explained in Section 8, the emergency alerts you send, and the messages you send to our administrators.
- **Emergency (SOS) requests from residents:** your barangay, the address and landmark you type, your description of the emergency, your location if you leave location sharing on, and any photo or video you take with the App.
- **Home visit requests:** the address, preferred date and time, and reason for the visit, and the GoBiker you choose.
- **Ratings:** the star rating and comment you give a GoBiker after a visit or an emergency response.
- **Check-up records:** when a GoBiker takes your vital signs, they record your name, address, contact number, age and barangay, your readings (blood pressure, pulse, respiratory rate, temperature and, if taken, blood sugar), any advice given, and that you agreed to the check-up.
- **Device information:** a push notification token from Google Firebase, so that we can send alerts to your phone.

**Collected automatically**

- **Technical data:** your IP address, browser and device type, the pages you request, and the date and time of access. This is recorded in our server logs and session records to keep the Site and App working and secure.
- **Cookies:** see our [Cookie Policy](/cookie-policy).

**GoBot conversations**

What you type in GoBot is sent to Google's Gemini service to generate a reply. We do not save GoBot conversations in our database; the conversation stays in your browser only until you leave or reload the page. Google processes these messages under its own terms, which may allow it to keep them for a limited time. Please do not share personal or sensitive information with GoBot.
TXT,
        ],
        [
            'title' => '4. Sensitive Personal Information',
            'content' => <<<'TXT'
Some of the information we handle is **sensitive personal information** under Section 3(l) of the Data Privacy Act, in particular:

- age and birthdate;
- health information, such as vital-sign readings and the details of an emergency;
- education, such as school status and school;
- government-issued documents and identifiers, such as barangay clearances and certificates of indigency or residency; and
- personal information about minors.

As required by Section 13 of the Data Privacy Act, we process sensitive personal information only:

- with the consent of the person concerned, given for a specific purpose before processing, or of their parent or guardian in the case of a minor;
- when necessary to protect the life and health of the person concerned or of another person, and the person concerned is not legally or physically able to give consent, such as during an emergency response; or
- when necessary to establish, exercise or defend legal claims, or when required by law or provided to a government or public authority.

Access to check-up records is limited to the GoBiker who recorded them, the resident they are linked to (see Section 9) and authorized PRI administrators.
TXT,
        ],
        [
            'title' => '5. How We Collect Personal Information',
            'content' => <<<'TXT'
We collect personal information:

- directly from you, when you fill in a form, use the App, email or call us, or take part in our activities;
- from GoBikers, who record check-ups and respond to emergencies;
- from your parent or guardian, or from someone who names you as their emergency contact;
- from Google, if you sign in to the App with your Google account; and
- automatically, when you use the Site or App (see Section 3).

If you give us information about another person, such as an emergency contact, please make sure that person knows about it and agrees.
TXT,
        ],
        [
            'title' => '6. How We Use Your Information',
            'content' => <<<'TXT'
We use personal information only for the purposes it was collected for, namely to:

- respond to your inquiries and messages;
- review volunteer applications, verify your identity and eligibility, organize trainings, classes and assignments, and send you updates such as approval and class schedule emails;
- handle training requests and chapter replication inquiries, prepare proposals and coordinate with partner institutions;
- create and manage App accounts, and review and approve GoBiker accounts;
- receive emergency requests, send the nearest available GoBiker, and keep volunteers on duty safe;
- arrange home visits, record check-ups, flag readings that need attention, and refer people to health facilities when needed;
- send App notifications about emergencies, visits, check-ups and announcements;
- process donations and training payments when online payments are available, prevent fraud, and keep the accounting and tax records required by law;
- operate, secure and improve the Site and App, including preventing spam, abuse and unauthorized access;
- prepare statistics and reports about our programs, such as the number of people served per barangay. Reports we publish or share outside PRI use aggregated or anonymized data that does not identify anyone; and
- comply with our legal obligations and with lawful requests from authorities.

**We do not sell personal information, and we do not use it for advertising or for marketing profiles.**
TXT,
        ],
        [
            'title' => '7. Our Legal Basis for Processing',
            'content' => <<<'TXT'
We process personal information only when Section 12 of the Data Privacy Act (and, for sensitive personal information, Section 13) allows it:

- **Consent** (Sections 12(a) and 13(a)): for example, volunteer applications and the IDs you upload, check-ups, the photo, video and location you attach to an SOS request, and optional third-party content on the Site. You may withdraw your consent at any time (see Section 14).
- **Contract** (Section 12(b)): to provide the App account you signed up for, handle the training and chapter requests you send us, and process donations.
- **Legal obligation** (Sections 12(c) and 13(f)): to keep tax and accounting records and to comply with lawful orders.
- **Protection of life and health** (Sections 12(d) and 13(c)): to respond to emergencies and to protect people who cannot give consent at that moment.
- **Legitimate interests** (Section 12(f)): to secure our systems, prevent fraud and abuse, coordinate GoBikers on duty, and improve our services, as long as these interests are not overridden by your fundamental rights and freedoms.
TXT,
        ],
        [
            'title' => '8. Location Information',
            'content' => <<<'TXT'
**If you are a GoBiker**

- **During a ronda,** the App sends your location about every 10 seconds, including when your screen is locked or the App is in the background. A notification on your phone shows that location sharing is on. Sharing stops when you end the ronda.
- **While the App is open** on your screen, it also sends your location about every 15 seconds, so that you appear as available on the map. This stops when you close the App or switch to another app.
- **When you press the emergency button,** the App sends your current location to our administrators.
- **Who sees it:** PRI administrators see the position of every GoBiker, including the last known position of GoBikers who are offline. While you are online, residents and other GoBikers in your barangay see your name, position and status.
- **What we keep:** only your most recent position, which is replaced with every update. We do not keep a history of your movements. The distance you ride is calculated and kept on your phone only.

**If you are a resident**

- The position shown on your own map stays on your phone and is not sent to us.
- When you send an SOS request, the App attaches your current location if "Share my location" is on, which is the default. You can switch it off before sending; we then rely on the address and landmark you type.
- Home visit requests use the address you type, not your phone's location.
TXT,
        ],
        [
            'title' => '9. Who Can See and Receive Your Information',
            'content' => <<<'TXT'
We share personal information only when necessary, and never more than is needed.

**Within the App**

- When an administrator sends a GoBiker to an emergency, that GoBiker sees the resident's name, mobile number, address, landmark, description and location, but not the photo or video.
- When a resident requests a home visit, the GoBiker they choose sees the resident's name, mobile number, age, address and reason for the visit.
- When a GoBiker records a check-up for a person whose name and mobile number match a resident account, the check-up is linked to that account, so the resident can see it in the App.
- Residents see the name, photo and barangay of the GoBiker responding to them. Residents' profile photos are seen only by PRI administrators.
- Authorized PRI administrators see the information they need to manage our programs.

**Service providers** (personal information processors) that process data on our behalf, under appropriate safeguards:

- **Hostinger** hosts the Site, the App's server, our database and uploaded files, and sends our emails.
- **Google** provides GoBot replies (Gemini), App sign-in (Google Sign-In), push notifications (Firebase Cloud Messaging) and the map on our Contact page (Google Maps, loaded only if you allow it). Notifications can include names, addresses and dates, and pass through Google's servers.
- **Esri** provides the satellite map in the App. Your phone requests the map images from Esri, which receives your IP address and the area of the map you are viewing.
- **OpenStreetMap** provides the map in our administrators' area.
- **PayPal and PayMongo** will process card, PayPal, GCash and online banking payments when online payments are available. We never see or store your full card or e-wallet details; the payment provider handles them under its own privacy policy.

We require our service providers to protect personal information and to use it only to provide their services to us.

**Other organizations**, only when needed:

- **Partner institutions**, such as barangay and municipal offices, disaster risk reduction and management offices, schools and health centers, to coordinate trainings, emergency response, referrals or program activities. Each partner receives only the information it needs.
- **Emergency and health services**, when necessary to protect someone's life or health.
- **Government authorities**, when required by law, regulation, court order or a lawful request.
- **A successor organization**, if PRI's programs are transferred to or merged with another organization, which must continue to protect your information as described in this policy.
TXT,
        ],
        [
            'title' => '10. Transfers Outside the Philippines',
            'content' => <<<'TXT'
Some of our service providers, including Hostinger, Google, Esri and PayPal, may store or process information on servers located outside the Philippines.

As required by Section 21 of the Data Privacy Act, PRI remains responsible for personal information that we transfer to them. We work only with reputable providers that maintain appropriate security measures, and we use contractual and other reasonable means to make sure your information receives a comparable level of protection.
TXT,
        ],
        [
            'title' => '11. Retention and Disposal',
            'content' => <<<'TXT'
We keep personal information only for as long as it is needed for the purposes in Section 6, or for as long as the law requires. Our retention periods are:

- **Contact messages:** up to 2 years after our last reply.
- **Volunteer applications and uploaded IDs:** while you are an active volunteer and up to 3 years after you leave the program. Applications that are not accepted, with their IDs, are deleted within 1 year.
- **Home training requests and their uploaded IDs, and chapter inquiries:** up to 3 years after the request is closed, or longer if covered by a written agreement.
- **Donation and payment records:** for the period required by Philippine tax, accounting and audit rules.
- **App accounts:** while your account is active. When you delete your account, it is removed right away (see Section 14).
- **Check-up records:** up to 5 years after the person's most recent check-up, for continuity of care and program reporting.
- **SOS requests:** up to 3 years after the request is closed. Photos and videos attached to an SOS request are kept for up to 1 year after it is closed.
- **Home visit requests, ratings, ronda records and messages to administrators:** up to 3 years.
- **GoBiker location:** only the most recent position, which is replaced with every update and deleted with the account.
- **Push notification tokens:** until you log out, the token stops working, or you delete your account.
- **Server logs:** 14 days. **Session records:** cleared automatically after they expire.
- **Database backups:** 7 days, after which each backup is overwritten.

We review our records at least once a year. When a retention period ends, we securely dispose of the information: electronic records are permanently deleted from our database and file storage, and any paper records are shredded. Deleted information may remain in our database backups until those backups are overwritten. We may keep information longer when the law requires it, or when it is needed for an ongoing matter such as a legal claim.
TXT,
        ],
        [
            'title' => '12. How We Protect Your Information',
            'content' => <<<'TXT'
As required by Section 20 of the Data Privacy Act, we use reasonable organizational, physical and technical measures to protect personal information against accidental loss, destruction, alteration and unauthorized access, use or disclosure.

**Organizational measures**

- We have designated a Data Protection Officer who oversees our compliance with the Data Privacy Act.
- Only authorized and approved personnel can view personal information, and only what they need for their role.
- Staff and volunteers who handle personal information are bound to keep it confidential.

**Physical measures**

- Our systems run in the data centers of our hosting provider, which are protected by physical access controls.
- Any paper records we keep are stored securely and shredded when no longer needed.

**Technical measures**

- Encrypted connections (HTTPS) for the Site and App.
- Passwords stored using strong one-way hashing, never in plain text.
- App sign-ins that expire after 30 days, and a password change signs you out on all other devices.
- Uploaded IDs and SOS photos and videos kept in private storage that only authorized administrators can open.
- Protection against common attacks, such as rate limiting, input validation and security headers.
- Daily database backups.
- Payment processing by PCI DSS-compliant providers, so card and e-wallet details never pass through our systems.
TXT,
        ],
        [
            'title' => '13. Personal Data Breach',
            'content' => <<<'TXT'
If a personal data breach occurs, we act at once to contain it and limit its effects.

When a breach involves sensitive personal information, or information that could be used for identity fraud, and is likely to put the people affected at real risk of serious harm, we will notify the **National Privacy Commission** and the affected people **within 72 hours** of becoming aware of it, as required by the Data Privacy Act and the rules of the NPC on personal data breach management.

The notice will describe the breach, the information involved, the measures we have taken, what you can do to protect yourself, and how to reach our Data Protection Officer.
TXT,
        ],
        [
            'title' => '14. Your Rights as a Data Subject',
            'content' => <<<'TXT'
Under Sections 16 to 18 of the Data Privacy Act, you have the right to:

- **Be informed** whether and how your personal information is being processed.
- **Access** your personal information and receive a copy of it.
- **Object** to the processing of your personal information, including withdrawing consent you have given.
- **Rectification**, meaning to correct inaccurate or incomplete information.
- **Erasure or blocking** of information that is incomplete, outdated, false, unlawfully obtained, no longer necessary, or processed without a valid basis.
- **Data portability**, meaning to receive your information in a commonly used electronic format, where applicable.
- **Damages** if you are harmed by inaccurate, incomplete, outdated, false, unlawfully obtained or unauthorized use of your personal information.
- **File a complaint** with the National Privacy Commission.

Your lawful heirs and assigns may exercise these rights if you pass away or become incapacitated.

**Deleting your App account**

- You can delete your App account at any time in the App, under **Profile → Delete account**. Your account, profile photo, location, device tokens, SOS requests with their photos and videos, home visit requests, ratings and messages are deleted.
- Check-up records about you are kept for the period in Section 11, but they are no longer linked to your account. Email us if you want them erased.
- GoBikers who have recorded check-ups cannot delete their account in the App, because those records are health information about the people they were taken from and must be kept. Email us, and we will close your account.

**Making a request**

To use any of your rights, email our Data Protection Officer at gobike@pydn.org with the subject "Data Privacy Request". Include your name, the email address or mobile number you used with us, and what you are asking for. To protect your information from people pretending to be you, we will verify your identity first. We respond within 15 working days.

We may decline or limit a request when the law allows it, for example when information must be kept for legal reasons, and we will tell you why. Withdrawing consent does not affect processing we carried out before you withdrew it.
TXT,
        ],
        [
            'title' => '15. Automated Processing',
            'content' => <<<'TXT'
We do not make decisions about you based solely on automated processing.

- GoBot's replies are generated automatically by artificial intelligence and may be wrong. GoBot does not make any decision about you.
- The App flags health readings that are outside normal ranges and suggests when follow-up check-ups can stop. These are aids only: PRI administrators review them and decide what to do.
TXT,
        ],
        [
            'title' => '16. Children and Minors',
            'content' => <<<'TXT'
- You must be at least **13 years old** to create an App account. If you are under 18, your parent or guardian must agree to your use of the App and to this policy.
- We do not knowingly collect personal information from children under 13 through the Site or App. If you believe a child under 13 has given us personal information, please contact us and we will delete it.
- Volunteers must be at least 18 years old, so we do not accept volunteer applications from minors.
- Check-ups of minors are done only with the consent of a parent or guardian, except in an emergency.
TXT,
        ],
        [
            'title' => '17. Photos and Videos at Our Activities',
            'content' => <<<'TXT'
We take photos and videos at public activities, trainings and community events, and may publish them on the Site, in our gallery, on social media and in reports. We avoid publishing images that reveal sensitive information, such as a person's health condition.

If you appear in our photos or videos and would like them removed, email gobike@pydn.org. We will remove them from the Site and our social media pages within a reasonable time.
TXT,
        ],
        [
            'title' => '18. Cookies',
            'content' => <<<'TXT'
The Site uses only the cookies it needs to work and stay secure, and loads content from other companies, such as Google Maps, only if you allow it. Our [Cookie Policy](/cookie-policy) lists every cookie we use and explains how to manage your choice.
TXT,
        ],
        [
            'title' => '19. Third-Party Websites and Services',
            'content' => <<<'TXT'
The Site and App link to, or use, websites and services run by others, such as social media pages, news articles, Google, Esri, PayPal and PayMongo. We are not responsible for their privacy practices, so please read their privacy policies before giving them your information.
TXT,
        ],
        [
            'title' => '20. Changes to This Policy',
            'content' => <<<'TXT'
We may update this Privacy Policy from time to time, for example when we add new services or when the law changes. We will post the updated policy on this page and change the "Last updated" date at the top. If we make significant changes, we will also post a notice on the Site or in the App, or contact you where appropriate.

This is **version 2.0** of this policy, effective November 13, 2026. It replaces the version dated October 22, 2026.
TXT,
        ],
        [
            'title' => '21. Contact Our Data Protection Officer',
            'content' => <<<'TXT'
If you have questions or concerns about this policy or how we handle your personal information, or want to use your rights, please contact:

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
