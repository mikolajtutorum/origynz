<x-layouts::legal>
    <div class="prose prose-sm max-w-none text-[#474747]">
        <h1 class="text-2xl font-bold text-[#2d2d2d] mb-2">Data Processing Agreement (DPA)</h1>
        <p class="text-sm text-zinc-500 mb-6">Last updated: {{ \Carbon\Carbon::parse('2026-04-19')->format('F j, Y') }}</p>

        <p>This Data Processing Agreement ("DPA") applies when Origynz processes personal data on behalf of a business customer ("Controller") in connection with the Origynz platform. It supplements the Terms of Service and is required where the General Data Protection Regulation (GDPR) or equivalent legislation applies to the processing.</p>

        <p>If you are an individual (not a business), this DPA does not apply to you — please refer to our <a href="{{ route('legal.privacy') }}" class="text-blue-600 underline">Privacy Policy</a>.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">1. Roles</h2>
        <p>The <strong>Controller</strong> is the business entity that determines the purposes and means of processing personal data using the Origynz platform.</p>
        <p>The <strong>Processor</strong> is Origynz, which processes personal data on behalf of the Controller according to documented instructions.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">2. Nature and Purpose of Processing</h2>
        <p>Origynz processes the personal data submitted by users of the Controller's account in order to provide the genealogy platform service, including storage, retrieval, export, and display of genealogy data.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">3. Categories of Data Subjects and Personal Data</h2>
        <p>The processing may involve personal data relating to the Controller's end users (account holders) and any persons whose data those users enter into the platform (including living and deceased individuals). Categories of data include: names, dates and places of birth, marriage, and death; biographical information; family relationships; photos and documents.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">4. Processor Obligations</h2>
        <p>Origynz shall:</p>
        <ul class="list-disc pl-5 space-y-1">
            <li>Process personal data only on the documented instructions of the Controller.</li>
            <li>Ensure that persons authorised to process the data are bound by confidentiality.</li>
            <li>Implement appropriate technical and organisational security measures (Article 32 GDPR).</li>
            <li>Not engage sub-processors without prior written consent of the Controller, except as listed in Section 6.</li>
            <li>Assist the Controller in fulfilling data subject rights requests (access, erasure, portability, etc.).</li>
            <li>Notify the Controller without undue delay (within 72 hours) upon becoming aware of a personal data breach.</li>
            <li>Delete or return all personal data to the Controller upon termination of the agreement, at the Controller's choice.</li>
            <li>Make available all information necessary to demonstrate compliance with GDPR Article 28 obligations.</li>
        </ul>

        <h2 class="text-lg font-semibold mt-6 mb-2">5. Controller Obligations</h2>
        <p>The Controller shall:</p>
        <ul class="list-disc pl-5 space-y-1">
            <li>Ensure it has a lawful basis for processing the personal data it submits to Origynz.</li>
            <li>Ensure that data subjects have been informed of the processing as required by applicable law.</li>
            <li>Provide clear, documented instructions to Origynz regarding any specific processing requirements.</li>
        </ul>

        <h2 class="text-lg font-semibold mt-6 mb-2">6. Sub-processors</h2>
        <p>Origynz may use the following categories of sub-processors to deliver the service:</p>
        <ul class="list-disc pl-5 space-y-1">
            <li><strong>Cloud hosting provider</strong> — for server infrastructure and data storage.</li>
            <li><strong>Email service provider</strong> — for transactional emails (account verification, password resets).</li>
        </ul>
        <p>Origynz will notify the Controller of any intended changes to the list of sub-processors, giving the Controller the opportunity to object. All sub-processors are bound by data processing agreements providing equivalent protections.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">7. International Data Transfers</h2>
        <p>Where data is transferred outside the European Economic Area (EEA), Origynz will ensure that appropriate safeguards are in place, such as the European Commission's Standard Contractual Clauses (SCCs), before the transfer takes place.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">8. Security Measures</h2>
        <p>Origynz maintains the following technical and organisational measures:</p>
        <ul class="list-disc pl-5 space-y-1">
            <li>Encryption of data in transit (TLS) and at rest.</li>
            <li>Hashed password storage (bcrypt).</li>
            <li>Role-based access controls.</li>
            <li>Regular backups with point-in-time recovery capability.</li>
            <li>Activity logging for security audit trails.</li>
            <li>Two-factor authentication (available to all users).</li>
        </ul>

        <h2 class="text-lg font-semibold mt-6 mb-2">9. Term and Termination</h2>
        <p>This DPA is effective for the duration of the Controller's use of the Origynz service and terminates automatically upon termination of the Terms of Service. Upon termination, Origynz will securely delete the Controller's data within 30 days unless the Controller requests a copy beforehand.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">10. Governing Law</h2>
        <p>This DPA is governed by the laws of Poland and the European Union, consistent with the Terms of Service.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">11. Contact</h2>
        <p>For DPA enquiries, data breach notifications, or sub-processor approval requests, contact:<br>
        Email: <a href="mailto:privacy@origynz.com" class="text-blue-600 underline">privacy@origynz.com</a></p>

        <div class="mt-8 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            <strong>Note for business customers:</strong> If you require a signed DPA to fulfil your GDPR obligations, please contact us at the email above to enter into a bilateral agreement.
        </div>
    </div>
</x-layouts::legal>
