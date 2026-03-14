<?php
// policy.php - Combined Terms and Privacy Policy page
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service & Privacy Policy | Logistics System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .policy-container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .policy-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .policy-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .policy-header p {
            opacity: 0.9;
        }
        
        .policy-tabs {
            display: flex;
            border-bottom: 2px solid #e5e7eb;
            background: #f9fafb;
        }
        
        .policy-tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            color: #6b7280;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .policy-tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: white;
        }
        
        .policy-content {
            padding: 40px;
            max-height: 500px;
            overflow-y: auto;
            scroll-behavior: smooth;
            background: white;
        }
        
        .policy-section {
            margin-bottom: 30px;
        }
        
        .policy-section h2 {
            color: #1f2937;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        
        .policy-section h3 {
            color: #374151;
            margin: 20px 0 10px;
            font-size: 1.2rem;
        }
        
        .policy-section p {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .policy-section ul {
            margin: 10px 0 20px 20px;
            color: #4b5563;
        }
        
        .policy-section li {
            margin-bottom: 8px;
        }
        
        .agreement-section {
            padding: 30px;
            background: #f9fafb;
            border-top: 2px solid #e5e7eb;
            text-align: center;
        }
        
        .agreement-checkbox {
            margin-bottom: 20px;
        }
        
        .agreement-checkbox label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #374151;
            cursor: pointer;
        }
        
        .agreement-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .agree-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 40px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, opacity 0.3s;
        }
        
        .agree-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .agree-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #6b7280;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .back-link:hover {
            color: #667eea;
        }
        
        .scroll-progress {
            position: sticky;
            top: 0;
            width: 100%;
            height: 4px;
            background: #e5e7eb;
            z-index: 100;
        }
        
        .scroll-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            width: 0%;
            transition: width 0.1s;
        }
        
        .highlight {
            animation: highlight 1s ease-in-out;
        }
        
        @keyframes highlight {
            0% { background-color: transparent; }
            50% { background-color: rgba(102, 126, 234, 0.2); }
            100% { background-color: transparent; }
        }
        
        .floating-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            display: none;
            animation: slideIn 0.3s;
            z-index: 1000;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="floating-alert" id="floatingAlert">
        <i class="fas fa-check-circle mr-2"></i>
        <span id="alertMessage">You have agreed to the terms!</span>
    </div>
    
    <div class="policy-container">
        <div class="policy-header">
            <h1><i class="fas fa-file-contract mr-2"></i>Terms & Privacy</h1>
            <p>Please read and agree to continue</p>
        </div>
        
        <div class="scroll-progress">
            <div class="scroll-progress-bar" id="progressBar"></div>
        </div>
        
        <div class="policy-tabs">
            <div class="policy-tab active" onclick="switchTab('terms')" id="termsTab">📋 Terms of Service</div>
            <div class="policy-tab" onclick="switchTab('privacy')" id="privacyTab">🔒 Privacy Policy</div>
        </div>
        
        <div class="policy-content" id="policyContent" onscroll="updateProgress()">
            <!-- Terms of Service Section -->
            <div id="termsSection">
                <div class="policy-section">
                    <h2>Terms of Service</h2>
                    <p>Last updated: March 14, 2026</p>
                    
                    <h3>1. Acceptance of Terms</h3>
                    <p>By accessing and using the Logistics System platform, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our services.</p>
                    
                    <h3>2. User Accounts</h3>
                    <p>You are responsible for maintaining the confidentiality of your account credentials. You agree to notify us immediately of any unauthorized use of your account.</p>
                    
                    <h3>3. Acceptable Use</h3>
                    <p>You agree to use the platform only for lawful purposes and in accordance with these terms. You may not:</p>
                    <ul>
                        <li>Use the platform in any way that violates applicable laws</li>
                        <li>Impersonate any person or entity</li>
                        <li>Interfere with the proper functioning of the platform</li>
                        <li>Attempt to gain unauthorized access to any part of the platform</li>
                    </ul>
                    
                    <h3>4. Service Availability</h3>
                    <p>We strive to maintain high availability of our services but cannot guarantee uninterrupted access. We reserve the right to modify or discontinue services with notice.</p>
                    
                    <h3>5. Limitation of Liability</h3>
                    <p>To the maximum extent permitted by law, Logistics System shall not be liable for any indirect, incidental, or consequential damages arising from your use of the platform.</p>
                </div>
            </div>
            
            <!-- Privacy Policy Section -->
            <div id="privacySection" style="display: none;">
                <div class="policy-section">
                    <h2>Privacy Policy</h2>
                    <p>Last updated: March 14, 2026</p>
                    
                    <h3>1. Information We Collect</h3>
                    <p>We collect information you provide directly to us, such as when you create an account, use our services, or communicate with us. This may include:</p>
                    <ul>
                        <li>Name, email address, and contact information</li>
                        <li>Account credentials</li>
                        <li>Usage data and preferences</li>
                        <li>Payment information (processed securely by third parties)</li>
                    </ul>
                    
                    <h3>2. How We Use Your Information</h3>
                    <p>We use the information we collect to:</p>
                    <ul>
                        <li>Provide, maintain, and improve our services</li>
                        <li>Communicate with you about updates and offers</li>
                        <li>Monitor and analyze usage patterns</li>
                        <li>Protect against unauthorized access and fraud</li>
                    </ul>
                    
                    <h3>3. Data Security</h3>
                    <p>We implement appropriate technical and organizational measures to protect your personal information. However, no method of transmission over the internet is 100% secure.</p>
                    
                    <h3>4. Your Rights</h3>
                    <p>You have the right to access, correct, or delete your personal information. You may also object to or restrict certain processing of your data.</p>
                    
                    <h3>5. Cookies</h3>
                    <p>We use cookies and similar technologies to enhance your experience and collect usage data. You can control cookies through your browser settings.</p>
                </div>
            </div>
        </div>
        
        <div class="agreement-section" id="agreementSection">
            <div class="agreement-checkbox">
                <label>
                    <input type="checkbox" id="agreeCheckbox" onchange="toggleAgreeButton()">
                    <span>I have read and agree to the <a href="#" onclick="scrollToTop()" class="text-blue-600 hover:text-blue-800">Terms of Service</a> and <a href="#" onclick="scrollToPrivacy()" class="text-blue-600 hover:text-blue-800">Privacy Policy</a></span>
                </label>
            </div>
            
            <button class="agree-btn" id="agreeBtn" disabled onclick="confirmAgreement()">
                <i class="fas fa-check-circle mr-2"></i>I Agree & Continue
            </button>
            
            <div>
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
    
    <script>
        let agreed = false;
        
        // Switch between tabs
        function switchTab(tab) {
            const termsTab = document.getElementById('termsTab');
            const privacyTab = document.getElementById('privacyTab');
            const termsSection = document.getElementById('termsSection');
            const privacySection = document.getElementById('privacySection');
            
            if (tab === 'terms') {
                termsTab.classList.add('active');
                privacyTab.classList.remove('active');
                termsSection.style.display = 'block';
                privacySection.style.display = 'none';
            } else {
                privacyTab.classList.add('active');
                termsTab.classList.remove('active');
                privacySection.style.display = 'block';
                termsSection.style.display = 'none';
            }
            
            // Reset scroll position
            document.getElementById('policyContent').scrollTop = 0;
        }
        
        // Scroll to specific sections
        function scrollToTop() {
            document.getElementById('policyContent').scrollTo({
                top: 0,
                behavior: 'smooth'
            });
            switchTab('terms');
            highlightSection('termsSection');
        }
        
        function scrollToPrivacy() {
            const privacySection = document.getElementById('privacySection');
            switchTab('privacy');
            highlightSection('privacySection');
        }
        
        function scrollToAgreement() {
            document.getElementById('agreementSection').scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
        
        // Highlight section
        function highlightSection(sectionId) {
            const section = document.getElementById(sectionId);
            section.classList.add('highlight');
            setTimeout(() => {
                section.classList.remove('highlight');
            }, 1000);
        }
        
        // Update scroll progress bar
        function updateProgress() {
            const content = document.getElementById('policyContent');
            const progress = (content.scrollTop / (content.scrollHeight - content.clientHeight)) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
            
            // Auto-check checkbox when scrolled to bottom
            if (progress > 95) {
                document.getElementById('agreeCheckbox').checked = true;
                toggleAgreeButton();
            }
        }
        
        // Toggle agree button based on checkbox
        function toggleAgreeButton() {
            const checkbox = document.getElementById('agreeCheckbox');
            const agreeBtn = document.getElementById('agreeBtn');
            agreeBtn.disabled = !checkbox.checked;
        }
        
        // Confirm agreement
        function confirmAgreement() {
            const checkbox = document.getElementById('agreeCheckbox');
            
            if (!checkbox.checked) {
                alert('Please check the box to confirm you have read and agree to the terms.');
                return;
            }
            
            // Show success message
            showAlert('✓ Thank you for agreeing to the terms!');
            
            // Here you can redirect back or set session variable
            setTimeout(() => {
                // Option 1: Redirect back to signup with agreement flag
                window.location.href = 'index.php?form=signup&agreed=true';
                
                // Option 2: Or set a session variable via AJAX
                // You can implement AJAX here to set a session variable
            }, 1500);
        }
        
        // Show floating alert
        function showAlert(message) {
            document.getElementById('alertMessage').textContent = message;
            const alert = document.getElementById('floatingAlert');
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 3000);
        }
        
        // Check if user came from signup
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('from') === 'signup') {
                // Auto-scroll to agreement after 2 seconds
                setTimeout(scrollToAgreement, 2000);
            }
        };
        
        // Prevent leaving without agreeing
        window.onbeforeunload = function() {
            const checkbox = document.getElementById('agreeCheckbox');
            if (!checkbox.checked) {
                return "You haven't agreed to the terms. Are you sure you want to leave?";
            }
        };
    </script>
</body>
</html>