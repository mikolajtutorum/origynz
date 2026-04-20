<x-layouts::legal>
    <div class="prose prose-sm max-w-none text-[#474747]">
        <h1 class="text-2xl font-bold text-[#2d2d2d] mb-2">Privacy Policy</h1>
        <p class="text-sm text-zinc-500 mb-6">Last updated: {{ \Carbon\Carbon::parse('2026-04-19')->format('F j, Y') }}</p>

        <p>Origynz ("we", "us", "our") operates the Origynz genealogy platform. This Privacy Policy explains what personal data we collect, why we collect it, how we use it, and your rights regarding that data. We are committed to complying with the General Data Protection Regulation (GDPR), the California Consumer Privacy Act (CCPA/CPRA), Canada's PIPEDA, Brazil's LGPD, and other applicable data protection laws.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">1. Who We Are (Data Controller)</h2>
        <p>Origynz is the data controller for personal data collected through this platform. If you have questions about how we process your data, contact us at: <a href="mailto:privacy@origynz.com" class="text-blue-600 underline">privacy@origynz.com</a>.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">2. What Data We Collect</h2>
        <p><strong>Account data:</strong> Your name, email address, password (stored as a secure hash), date of birth, country of residence, and preferred language.</p>
        <p><strong>Genealogy data:</strong> Information you enter about family members, including names, dates and places of birth, marriage, and death; biographical notes; cause of death; physical descriptions; and family relationships.</p>
        <p><strong>Media:</strong> Photos, documents, and other files you upload.</p>
        <p><strong>Activity logs:</strong> A record of actions you take within the platform (e.g., adding a person, exporting a tree) for security and audit purposes.</p>
        <p><strong>Technical data:</strong> IP address, browser type, session data, and cookies necessary for the service to function.</p>
        <p><strong>Social login data:</strong> If you sign in via a third-party provider (e.g., Google), we receive your name and email from that provider. We do not store your provider password or access tokens beyond the session.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">3. Why We Collect It (Legal Basis)</h2>
        <ul class="list-disc pl-5 space-y-1">
            <li><strong>Contract performance:</strong> To create and manage your account and provide the genealogy service.</li>
            <li><strong>Legitimate interest:</strong> Activity logs for security, fraud prevention, and service improvement.</li>
            <li><strong>Legal obligation:</strong> Retaining certain records as required by applicable law.</li>
            <li><strong>Consent:</strong> Optional features such as sharing your family tree in the Global Tree require your explicit consent, which you may withdraw at any time in Settings.</li>
        </ul>

        <h2 class="text-lg font-semibold mt-6 mb-2">4. Data About Deceased Persons</h2>
        <p>Genealogy necessarily involves data about deceased individuals. We apply a 100-year rule: persons with no recorded death date and a birth date more than 100 years ago, or persons with a recorded death date, are treated as deceased and may appear in public or Global Tree views subject to your privacy settings. Living persons are anonymised as "Private Person" in all public views. Some jurisdictions (e.g., Germany, France) grant posthumous data rights to heirs — if you have such a claim, contact us at the address above.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">5. How Long We Keep Your Data</h2>
        <ul class="list-disc pl-5 space-y-1">
            <li><strong>Account and genealogy data:</strong> Retained for as long as your account is active. Deleted within 30 days of account deletion.</li>
            <li><strong>Activity logs:</strong> Deleted upon account deletion.</li>
            <li><strong>Media files:</strong> Deleted upon account deletion or when you remove them.</li>
            <li><strong>Backups:</strong> May persist in encrypted backups for up to 90 days after deletion.</li>
        </ul>

        <h2 class="text-lg font-semibold mt-6 mb-2">6. Who We Share Data With</h2>
        <p>We do not sell your personal data. We may share data with:</p>
        <ul class="list-disc pl-5 space-y-1">
            <li><strong>Hosting and infrastructure providers</strong> (acting as data processors under our instruction).</li>
            <li><strong>Social login providers</strong> (only when you choose to use them).</li>
            <li><strong>Law enforcement</strong> when required by law or court order.</li>
        </ul>
        <p>All processors are required to maintain appropriate security measures and may not use your data for their own purposes.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">7. International Data Transfers</h2>
        <p>If you are located outside the country where our servers are hosted, your data may be transferred internationally. Where required, we rely on Standard Contractual Clauses (SCCs) approved by the European Commission, or equivalent safeguards, to protect such transfers.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">8. Cookies</h2>
        <p>We use the following types of cookies:</p>
        <ul class="list-disc pl-5 space-y-1">
            <li><strong>Essential cookies:</strong> Required for authentication and session management. These cannot be disabled without breaking the service.</li>
            <li><strong>Preference cookies:</strong> Store your language and display preferences.</li>
        </ul>
        <p>We do not use advertising or tracking cookies. You can manage your cookie consent using the banner at the bottom of this page.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">9. Your Rights</h2>
        <p>Depending on your jurisdiction, you have the right to:</p>
        <ul class="list-disc pl-5 space-y-1">
            <li><strong>Access:</strong> Request a copy of your personal data. Use <a href="{{ route('settings.data-export') }}" class="text-blue-600 underline">Data Export</a> in Settings, or email us.</li>
            <li><strong>Rectification:</strong> Correct inaccurate data in your profile settings.</li>
            <li><strong>Erasure:</strong> Delete your account and all associated data in Settings → Delete Account.</li>
            <li><strong>Restriction:</strong> Ask us to pause processing while a dispute is resolved.</li>
            <li><strong>Portability:</strong> Download your data as JSON or GEDCOM.</li>
            <li><strong>Objection:</strong> Object to processing based on legitimate interest.</li>
            <li><strong>Withdraw consent:</strong> Withdraw any consent (e.g., Global Tree sharing) at any time in Settings.</li>
            <li><strong>Lodge a complaint:</strong> File a complaint with your national data protection authority (e.g., the Polish UODO for EU users).</li>
        </ul>
        <p>To exercise these rights, email <a href="mailto:privacy@origynz.com" class="text-blue-600 underline">privacy@origynz.com</a>. We will respond within 30 days.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">10. California Residents (CCPA/CPRA)</h2>
        <p>California residents have additional rights. We do not sell personal information as defined by the CCPA. To exercise your right to opt out of any future data sharing, visit our <a href="{{ route('legal.ccpa') }}" class="text-blue-600 underline">CCPA Opt-Out page</a>. For requests regarding access, deletion, or correction of your data, email <a href="mailto:privacy@origynz.com" class="text-blue-600 underline">privacy@origynz.com</a>. We will not discriminate against you for exercising your privacy rights.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">11. Children (COPPA / GDPR-K)</h2>
        <p>Our service is not directed at children under the age of 13. We do not knowingly collect personal data from anyone under 13. If you believe we have inadvertently collected data from a child, contact us and we will delete it promptly. Users between 13 and 16 in the EU require verifiable parental consent under GDPR Article 8 — if you are in this age range, please have a parent or guardian contact us.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">12. Security</h2>
        <p>We implement industry-standard security measures including encrypted storage, hashed passwords, HTTPS transport, and access controls. In the event of a personal data breach that is likely to result in a risk to your rights and freedoms, we will notify you and the relevant supervisory authority within 72 hours of becoming aware of it.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">13. Changes to This Policy</h2>
        <p>We may update this policy from time to time. We will notify registered users of material changes by email at least 30 days before they take effect. Continued use of the service after that date constitutes acceptance.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">14. Contact</h2>
        <p>Data Controller: Origynz<br>
        Email: <a href="mailto:privacy@origynz.com" class="text-blue-600 underline">privacy@origynz.com</a></p>
    </div>
</x-layouts::legal>
