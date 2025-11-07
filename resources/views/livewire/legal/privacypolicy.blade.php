<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new
#[Layout('components.layouts.public')] // change to your actual layout if needed
#[Title('Privacy Policy')]
class extends Component {
    //
}; ?>

<div class="max-w-4xl mx-auto px-4 py-10 space-y-6">
    <h1 class="text-3xl font-bold mb-4">Privacy Policy</h1>
    <p class="text-sm text-gray-500">Effective Date: 11/06/2025</p>

    <p>
        This Privacy Policy explains how GH Business Outsourcing Inc. (“GHBO,” “we,” “our,” “us”)
        collects, uses, stores, and protects your personal information when you visit or use our
        career website and employee portal (“Portal”).
    </p>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">1. Company Information</h2>
        <p><strong>Company:</strong> GH Business Outsourcing Inc.</p>
        <p><strong>Address:</strong> 234 Nillas St, Sibulan, Negros Oriental, Philippines</p>
        <p><strong>Website:</strong> <a href="https://portal.gh-businesssolutions.com" class="text-blue-600 underline">https://portal.gh-businesssolutions.com</a></p>
        <p><strong>Email:</strong> <a href="mailto:info@gh-businesssolutions.com" class="text-blue-600 underline">info@gh-businesssolutions.com</a></p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">2. Applicability</h2>
        <p>
            This Privacy Policy applies to all job applicants, employees, and other users who access or use
            our Portal for job applications, employee self-service, HR-related functions, and other business
            purposes.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">3. Information We Collect</h2>
        <p>We may collect the following categories of personal information:</p>
        <ul class="list-disc list-inside space-y-1">
            <li>
                <strong>Personal Identification Information:</strong> Full name, contact number, email address,
                date of birth, gender, and residential address.
            </li>
            <li>
                <strong>Employment &amp; Application Information:</strong> Resume/CV, education, employment history,
                skills, certifications, references, and answers to screening questions.
            </li>
            <li>
                <strong>Account Information:</strong> Username, password, and activity logs related to your use of the Portal.
            </li>
            <li>
                <strong>Employee Information (for current staff):</strong> Government identification numbers (e.g., TIN,
                SSS, PhilHealth, Pag-IBIG), job position and department, compensation details, attendance and performance records,
                and other HR-related information.
            </li>
            <li>
                <strong>Technical Information:</strong> IP address, browser type, operating system, device information, and
                usage data collected through cookies or similar technologies.
            </li>
        </ul>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">4. How We Use Your Information</h2>
        <p>We process your personal data for the following purposes:</p>
        <ul class="list-disc list-inside space-y-1">
            <li>To review, evaluate, and process job applications.</li>
            <li>To create, manage, and maintain employee accounts and records.</li>
            <li>To facilitate HR processes such as payroll, benefits, performance evaluation, and timekeeping.</li>
            <li>To communicate with you regarding your application, employment, or Portal-related updates.</li>
            <li>To comply with legal, regulatory, and reporting obligations under Philippine laws and regulations.</li>
            <li>To maintain and improve the security, stability, and usability of the Portal.</li>
            <li>To improve our recruitment, HR, and business operations based on aggregated and anonymized data.</li>
        </ul>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">5. Legal Basis for Processing</h2>
        <p>We process your personal information under the following legal bases, consistent with the Philippine Data Privacy Act of 2012:</p>
        <ul class="list-disc list-inside space-y-1">
            <li><strong>Consent</strong> – When you submit your application or create an account on the Portal.</li>
            <li><strong>Contractual Necessity</strong> – To perform pre-employment and employment-related obligations.</li>
            <li><strong>Legal Obligations</strong> – To comply with labor, tax, and other regulatory requirements.</li>
            <li><strong>Legitimate Interests</strong> – To improve our systems, enhance security, and conduct internal administration.</li>
        </ul>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">6. Data Sharing and Disclosure</h2>
        <p>We may share your personal data with the following, on a need-to-know basis:</p>
        <ul class="list-disc list-inside space-y-1">
            <li>Authorized HR personnel, managers, and relevant internal departments of GH Business Outsourcing Inc.</li>
            <li>Clients and partner companies, when your application or employment relates to a specific project or placement.</li>
            <li>Third-party service providers (e.g., IT, hosting, payroll, communication tools) who are bound by confidentiality obligations.</li>
            <li>Government agencies and regulators, when required by law, regulation, subpoena, or court order.</li>
        </ul>
        <p>
            We do <strong>not</strong> sell, rent, or trade your personal data to any third parties.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">7. Data Retention</h2>
        <p>
            We retain personal data only for as long as necessary for the purposes for which it was collected or as required by applicable laws:
        </p>
        <ul class="list-disc list-inside space-y-1">
            <li>
                <strong>Applicants:</strong> Your application data may be retained for up to two (2) years for record-keeping and
                consideration for future opportunities, unless you request earlier deletion where legally permissible.
            </li>
            <li>
                <strong>Employees:</strong> Employee records are retained for the duration of employment and for a period thereafter
                as required by employment, tax, and regulatory requirements.
            </li>
        </ul>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">8. Data Protection and Security</h2>
        <p>
            We implement appropriate organizational, physical, and technical measures to safeguard your personal data against unauthorized
            access, alteration, disclosure, or destruction. Access to personal data is restricted to authorized personnel who have a legitimate
            need to know.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">9. Your Rights Under the Data Privacy Act</h2>
        <p>You have the following rights under the Philippine Data Privacy Act of 2012:</p>
        <ul class="list-disc list-inside space-y-1">
            <li>The right to be informed that your personal data is being collected and processed.</li>
            <li>The right to access and obtain a copy of your personal data that we hold.</li>
            <li>The right to rectify or correct inaccurate or outdated personal information.</li>
            <li>The right to object to certain forms of data processing, subject to legal and contractual limitations.</li>
            <li>The right to request deletion or blocking of personal data where appropriate.</li>
            <li>The right to data portability, where applicable.</li>
            <li>The right to file a complaint with the National Privacy Commission (NPC) if you believe your rights have been violated.</li>
        </ul>
        <p>
            To exercise any of these rights, you may contact us at
            <a href="mailto:info@gh-businesssolutions.com" class="text-blue-600 underline">info@gh-businesssolutions.com</a>.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">10. Cookies and Similar Technologies</h2>
        <p>
            Our Portal may use cookies or similar technologies to enhance your user experience, remember your preferences, and help us
            understand how the Portal is used. You may configure your browser to block or delete cookies; however, this may affect certain
            functionalities of the Portal.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">11. Updates to This Privacy Policy</h2>
        <p>
            We may update this Privacy Policy from time to time to reflect changes in our practices or legal requirements. The updated
            version will be posted on this page with a revised effective date. We encourage you to review this Policy periodically.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">12. Contact Information</h2>
        <p>If you have any questions, concerns, or requests regarding this Privacy Policy or your personal data, you may contact:</p>
        <p><strong>Data Protection Officer</strong></p>
        <p>GH Business Outsourcing Inc.</p>
        <p>234 Nillas St, Sibulan, Negros Oriental, Philippines</p>
        <p>Email: <a href="mailto:info@gh-businesssolutions.com" class="text-blue-600 underline">info@gh-businesssolutions.com</a></p>
    </section>
</div>
