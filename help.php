<?php
session_start();
require_once 'config/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Help & Support - UEP Fitness Gym';
include 'header.php';
?>

<style>
.help-container {
    max-width: 80rem;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 2rem;
    text-align: center;
}

.page-header h2 {
    font-size: 2.25rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-header p {
    color: #64748b;
    font-size: 1.125rem;
}

.quick-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.quick-card {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    padding: 1.5rem;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: all 0.3s ease;
}

.quick-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
}

.quick-card.blue {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}

.quick-card.green {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
}

.quick-card.purple {
    background: linear-gradient(135deg, #e9d5ff 0%, #d8b4fe 100%);
}

.card-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.card-icon.blue { background: #3b82f6; }
.card-icon.green { background: #10b981; }
.card-icon.purple { background: #a855f7; }

.card-icon svg {
    width: 1.5rem;
    height: 1.5rem;
    color: white;
}

.quick-card h3 {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.5rem;
}

.quick-card p {
    font-size: 0.875rem;
    color: #475569;
}

.content-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.content-card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(to right, #ffffff, #f8fafc);
}

.content-card-header h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
}

.content-card-body {
    padding: 1.5rem;
}

.faq-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.faq-item {
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 1.5rem;
}

.faq-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.faq-question {
    font-size: 1.125rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.faq-number {
    width: 1.5rem;
    height: 1.5rem;
    background: #dbeafe;
    color: #2563eb;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 700;
    flex-shrink: 0;
}

.faq-answer {
    color: #475569;
    margin-left: 2rem;
}

.guides-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.guide-card {
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    transition: all 0.2s ease;
}

.guide-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.guide-card h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.guide-card svg {
    width: 1.25rem;
    height: 1.25rem;
    color: #2563eb;
}

.guide-card p {
    font-size: 0.875rem;
    color: #64748b;
}

.contact-card {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.contact-card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #93c5fd;
}

.contact-card-body {
    padding: 1.5rem;
}

.contact-card-body > p {
    color: #1e40af;
    margin-bottom: 1rem;
}

.contact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.contact-icon {
    width: 2.5rem;
    height: 2.5rem;
    background: #3b82f6;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.contact-icon svg {
    width: 1.25rem;
    height: 1.25rem;
    color: white;
}

.contact-info h4 {
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 0.25rem;
}

.contact-info p {
    font-size: 0.875rem;
    color: #475569;
}

.contact-info .contact-value {
    color: #2563eb;
    font-weight: 500;
}

.business-hours {
    margin-top: 1.5rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 0.75rem;
}

.business-hours p {
    font-size: 0.875rem;
    color: #1e40af;
}

@media (max-width: 768px) {
    .page-header h2 {
        font-size: 1.875rem;
    }
    
    .quick-cards, .guides-grid, .contact-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="help-container">
  <!-- Page Header -->
  <div class="page-header">
    <h2>Help & Support</h2>
    <p>Find answers to common questions and get assistance</p>
  </div>

  <!-- Quick Help Cards -->
  <div class="quick-cards">
    <div class="quick-card blue">
      <div class="card-icon blue">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 5.25h.008v.008H12v-.008z"/>
        </svg>
      </div>
      <h3>FAQs</h3>
      <p>Common questions and answers</p>
    </div>

    <div class="quick-card green">
      <div class="card-icon green">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
        </svg>
      </div>
      <h3>User Guide</h3>
      <p>Step-by-step instructions</p>
    </div>

    <div class="quick-card purple">
      <div class="card-icon purple">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
        </svg>
      </div>
      <h3>Contact Support</h3>
      <p>Get help from our team</p>
    </div>
  </div>

  <!-- FAQ Section -->
  <div class="content-card">
    <div class="content-card-header">
      <h3>Frequently Asked Questions</h3>
    </div>
    <div class="content-card-body">
      <div class="faq-list">
        <!-- FAQ Item 1 -->
        <div class="faq-item">
          <h4 class="faq-question">
            <span class="faq-number">1</span>
            How do I update my profile information?
          </h4>
          <p class="faq-answer">You can update your profile information by navigating to the Settings page from the user menu. Click on your profile picture in the top right corner, then select "Settings". From there, you can update your full name, email address, and change your password.</p>
        </div>

        <!-- FAQ Item 2 -->
        <div class="faq-item">
          <h4 class="faq-question">
            <span class="faq-number">2</span>
            How do I check my attendance?
          </h4>
          <p class="faq-answer">Go to the Attendance section from the main navigation menu. You'll see a list of your attendance records, including check-in and check-out times. The system tracks your gym visits automatically using RFID technology.</p>
        </div>

        <!-- FAQ Item 3 -->
        <div class="faq-item">
          <h4 class="faq-question">
            <span class="faq-number">3</span>
            How can I view my billing history?
          </h4>
          <p class="faq-answer">Navigate to the Billing section from the main menu. Here you can view all your payment history, upcoming payments, and membership fees. You can also see the status of each payment (Paid, Pending, or Overdue).</p>
        </div>

        <!-- FAQ Item 4 -->
        <div class="faq-item">
          <h4 class="faq-question">
            <span class="faq-number">4</span>
            What should I do if I forgot my password?
          </h4>
          <p class="faq-answer">If you've forgotten your password, please contact the gym administrator or support team. They can help you reset your password. For security reasons, password resets must be done through authorized personnel.</p>
        </div>

        <!-- FAQ Item 5 -->
        <div class="faq-item">
          <h4 class="faq-question">
            <span class="faq-number">5</span>
            How do I track my fitness progress?
          </h4>
          <p class="faq-answer">You can track your fitness progress in the Progress section. Here you can view your workout records, including exercises, sets, reps, and weights. The system also tracks your vital signs such as weight, body fat percentage, and other health metrics.</p>
        </div>

        <!-- FAQ Item 6 -->
        <div class="faq-item">
          <h4 class="faq-question">
            <span class="faq-number">6</span>
            What if my RFID card is not working?
          </h4>
          <p class="faq-answer">If your RFID card is not working, please visit the front desk. The staff can check your card registration and help troubleshoot the issue. They may need to update your card information or issue a replacement card if necessary.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Contact Support Section -->
  <div class="contact-card">
    <div class="contact-card-header">
      <h3>Need More Help?</h3>
    </div>
    <div class="contact-card-body">
      <p>If you can't find the answer you're looking for, our support team is here to help.</p>
      <div class="contact-grid">
        <div class="contact-item">
          <div class="contact-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
            </svg>
          </div>
          <div class="contact-info">
            <h4>Phone Support</h4>
            <p>Call us during business hours</p>
            <p class="contact-value">(02) 123-4567</p>
          </div>
        </div>

        <div class="contact-item">
          <div class="contact-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
            </svg>
          </div>
          <div class="contact-info">
            <h4>Email Support</h4>
            <p>Send us an email anytime</p>
            <p class="contact-value">support@uepgym.com</p>
          </div>
        </div>
      </div>

      <div class="business-hours">
        <p><strong>Business Hours:</strong> Sunday to Saturday, 6:00 AM - 6:00 PM</p>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

