<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new
#[Layout('components.layouts.public')] // change to your actual layout if needed
#[Title('Terms of Service')]
class extends Component {
    //
};

?>

<div class="max-w-4xl mx-auto px-4 py-10 space-y-6">
    <h1 class="text-3xl font-bold mb-4">Terms of Service</h1>
    <p class="text-sm text-gray-500">Effective Date: 11/06/2025</p>

    <p>
        These Terms of Service (“Terms”) govern your access to and use of the career website and employee portal
        (“Portal”) operated by GH Business Outsourcing Inc. (“GHBO,” “we,” “our,” “us”). By accessing or using the Portal,
        you agree to be bound by these Terms and our Privacy Policy. If you do not agree, you must not use the Portal.
    </p>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">1. Company Information</h2>
        <p><strong>Company:</strong> GH Business Outsourcing Inc.</p>
        <p><strong>Address:</strong> 234 Nillas St, Sibulan, Negros Oriental, Philippines</p>
        <p><strong>Website:</strong> <a href="https://portal.gh-businesssolutions.com" class="text-blue-600 underline">https://portal.gh-businesssolutions.com</a></p>
        <p><strong>Email:</strong> <a href="mailto:info@gh-businesssolutions.com" class="text-blue-600 underline">info@gh-businesssolutions.com</a></p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">2. Eligibility</h2>
        <p>
            By using the Portal, you represent and warrant that you are at least 18 years of age and that all information you
            provide is accurate, complete, and current. The Portal is intended for applicants seeking employment and employees
            using HR-related services.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">3. Account Registration and Security</h2>
        <p>
            To use certain features of the Portal, you may be required to create an account. You are responsible for:
        </p>
        <ul class="list-disc list-inside space-y-1">
            <li>Maintaining the confidentiality of your username and password.</li>
            <li>Ensuring that your registration and profile information is accurate and up to date.</li>
            <li>Immediately notifying GHBO of any unauthorized use of your account or any other breach of security.</li>
        </ul>
        <p>
            GHBO is not liable for any loss or damage arising from your failure to protect your account credentials.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">4. Permitted Use of the Portal</h2>
        <p>You agree to use the Portal only for lawful purposes, including:</p>
        <ul class="list-disc list-inside space-y-1">
            <li>Submitting truthful and accurate job applications.</li>
            <li>Accessing your employment-related information and HR services.</li>
            <li>Communicating with GHBO for legitimate career or employment-related matters.</li>
        </ul>
        <p>You agree <strong>not</strong> to:</p>
        <ul class="list-disc list-inside space-y-1">
            <li>Provide false, misleading, or fraudulent information.</li>
            <li>Access or attempt to access accounts or information belonging to other users without authorization.</li>
            <li>Interfere with or disrupt the operation of the Portal or its servers.</li>
            <li>Upload or transmit any malicious code, viruses, or harmful content.</li>
            <li>Use the Portal for any unlawful, abusive, or unauthorized purpose.</li>
        </ul>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">5. Intellectual Property</h2>
        <p>
            All content and materials available on the Portal, including but not limited to text, graphics, logos, icons,
            software, and design elements, are the property of GH Business Outsourcing Inc. or its licensors and are protected
            by applicable intellectual property laws.
        </p>
        <p>
            You may view and print content for your personal, non-commercial use in relation to your application or employment,
            but you may not copy, modify, reproduce, distribute, or create derivative works without our prior written consent.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">6. No Employment Guarantee</h2>
        <p>
            Use of the Portal and submission of a job application does not guarantee employment, interview, or any specific outcome.
            GHBO reserves the right to accept or reject any application at its sole discretion and without obligation to provide
            feedback or reasons for decisions.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">7. Third-Party Links and Services</h2>
        <p>
            The Portal may contain links to third-party websites or services that are not owned or controlled by GHBO. We are not
            responsible for the content, privacy policies, or practices of any third-party sites. Accessing such links is at your
            own risk, and we encourage you to review their terms and policies.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">8. Disclaimer of Warranties</h2>
        <p>
            The Portal is provided on an “as is” and “as available” basis. GHBO makes no representations or warranties of any kind,
            express or implied, regarding the operation of the Portal or the information, content, or materials included therein.
        </p>
        <p>
            To the fullest extent permitted by law, GHBO disclaims all warranties, including but not limited to implied warranties
            of merchantability, fitness for a particular purpose, and non-infringement.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">9. Limitation of Liability</h2>
        <p>
            To the maximum extent permitted by law, GHBO shall not be liable for any indirect, incidental, special, consequential,
            or punitive damages, or any loss of profits or revenues, whether incurred directly or indirectly, arising from your use
            of or inability to use the Portal.
        </p>
        <p>
            GHBO’s total liability to you for any claims arising out of or relating to these Terms or the use of the Portal shall
            not exceed any amount you have paid (if any) to GHBO for use of the Portal.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">10. Suspension and Termination</h2>
        <p>
            GHBO reserves the right, at its sole discretion, to suspend or terminate your access to the Portal at any time and
            without prior notice if you violate these Terms, our Privacy Policy, or any applicable law, or if your use poses a
            security or operational risk.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">11. Changes to These Terms</h2>
        <p>
            We may update or modify these Terms from time to time. Any changes will be posted on this page with an updated effective
            date. Your continued use of the Portal after the posting of changes constitutes your acceptance of the revised Terms.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">12. Governing Law and Jurisdiction</h2>
        <p>
            These Terms shall be governed by and construed in accordance with the laws of the Republic of the Philippines. Any dispute
            arising out of or in connection with these Terms shall be submitted to the exclusive jurisdiction of the proper courts
            of Dumaguete City, Negros Oriental, Philippines.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold">13. Contact Information</h2>
        <p>If you have any questions or concerns about these Terms, you may contact us at:</p>
        <p><strong>GH Business Outsourcing Inc.</strong></p>
        <p>234 Nillas St, Sibulan, Negros Oriental, Philippines</p>
        <p>Email: <a href="mailto:info@gh-businesssolutions.com" class="text-blue-600 underline">info@gh-businesssolutions.com</a></p>
        <p>Website: <a href="https://portal.gh-businesssolutions.com" class="text-blue-600 underline">https://portal.gh-businesssolutions.com</a></p>
    </section>
</div>
