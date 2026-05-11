<?php
// footer.php - UEP Fitness Gym
?>

  </div> <!-- End main container -->
  <footer class="footer">
    &copy; <?php echo date('Y'); ?> UEP Fitness Gym. All rights reserved.
  </footer>

  <script>
    // Mobile sidebar toggle - wrapped in IIFE to prevent variable conflicts
    (function() {
      const menuBtn = document.getElementById('mobile-menu-btn');
      const mobileMenu = document.getElementById('mobile-menu');
      const menuOverlay = document.getElementById('mobile-menu-overlay');
      const closeBtn = document.getElementById('mobile-menu-close');
      const userMenuBtn = document.getElementById('user-menu-btn');
      const userMenu = document.getElementById('user-menu');

      const closeMenu = () => {
        if (mobileMenu) {
          mobileMenu.classList.remove('show');
          menuBtn.setAttribute('aria-expanded', 'false');
        }
        if (menuOverlay) {
          menuOverlay.classList.remove('show');
        }
      };

      if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', () => {
          mobileMenu.classList.toggle('show');
          if (menuOverlay) {
            menuOverlay.classList.toggle('show');
          }
          menuBtn.setAttribute('aria-expanded', String(mobileMenu.classList.contains('show')));
        });
      }

      // Close sidebar when clicking overlay
      if (menuOverlay) {
        menuOverlay.addEventListener('click', closeMenu);
      }

      // Close sidebar when clicking close button
      if (closeBtn) {
        closeBtn.addEventListener('click', closeMenu);
      }

      // Close sidebar when clicking a menu item
      if (mobileMenu) {
        mobileMenu.addEventListener('click', (e) => {
          if (e.target.tagName === 'A') {
            closeMenu();
          }
        });
      }

      // User menu toggle
      if (userMenuBtn && userMenu) {
        userMenuBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          userMenu.classList.toggle('show');
          userMenuBtn.setAttribute('aria-expanded', String(userMenu.classList.contains('show')));
        });

        document.addEventListener('click', (e) => {
          if (userMenu.classList.contains('show')) {
            const target = e.target;
            const within = userMenu.contains(target) || userMenuBtn.contains(target);
            if (!within) {
              userMenu.classList.remove('show');
              userMenuBtn.setAttribute('aria-expanded', 'false');
            }
          }
        });

        document.addEventListener('keydown', (e) => {
          if (e.key === 'Escape' && userMenu.classList.contains('show')) {
            userMenu.classList.remove('show');
            userMenuBtn.setAttribute('aria-expanded', 'false');
          }
        });
      }
    })();
  </script>

    <!-- Member Modal Logic (for members.php) -->
    <script>
      function openAddModal() {
        const modal = document.getElementById('memberModal');
        if (!modal) return;
        // Reset form fields for add
        document.getElementById('modalTitle').textContent = 'Add New Member';
        document.getElementById('formAction').value = 'add_member';
        document.getElementById('editMemberId').value = '';
        document.getElementById('username').value = '';
        if(document.getElementById('first_name')) document.getElementById('first_name').value = '';
        if(document.getElementById('last_name')) document.getElementById('last_name').value = '';
        if(document.getElementById('middle_name')) document.getElementById('middle_name').value = '';
        if(document.getElementById('email')) document.getElementById('email').value = '';
        document.getElementById('gender').value = '';
        document.getElementById('contact_number').value = '';
        document.getElementById('address').value = '';
        document.getElementById('date_of_birth').value = '';
        document.getElementById('rfid_card_number').value = '';
        document.getElementById('membership_plan').value = '';
        document.getElementById('membership_status').value = 'Pending';
        if(document.getElementById('profile_picture')) document.getElementById('profile_picture').value = '';
        // Enable all fields if they were disabled from View mode
        document.querySelectorAll('#memberModal input, #memberModal select, #memberModal textarea').forEach(f => f.removeAttribute('disabled'));
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      }

      function closeModal() {
        const modal = document.getElementById('memberModal');
        if (modal) modal.classList.add('hidden');
      }

      // Optional: Close modal on Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
      });
    </script>

      <!-- Modal Logic for All Main Pages -->
      <script>
        // Equipment Modal
        if (document.getElementById('equipmentModal')) {
          window.openAddModal = function() {
            document.getElementById('modalTitle').textContent = 'Add Equipment';
            document.getElementById('formAction').value = 'add_equipment';
            document.getElementById('editEquipmentId').value = '';
            document.querySelector('#equipmentModal form').reset();
            document.getElementById('equipmentModal').classList.remove('hidden');
          };
          window.closeModal = function() {
            document.getElementById('equipmentModal').classList.add('hidden');
          };
        }

        // Billing Modal - Note: billing.php has its own openAddModal function, so this is a fallback
        if (document.getElementById('billingModal') && typeof window.openAddModal === 'undefined') {
          window.openAddModal = function() {
            const modal = document.getElementById('billingModal');
            if (modal) {
              document.getElementById('modalTitle').textContent = 'Add Billing Record';
              document.getElementById('formAction').value = 'add_billing';
              document.getElementById('editBillingId').value = '';
              const form = document.querySelector('#billingModal form');
              if (form) form.reset();
              modal.classList.add('show');
            }
          };
          window.closeModal = function() {
            const modal = document.getElementById('billingModal');
            if (modal) modal.classList.remove('show');
          };
        }

        // Trainer Modal
        if (document.getElementById('trainerModal')) {
          window.openAddModal = function() {
            document.getElementById('modalTitle').textContent = 'Add Trainer';
            document.getElementById('formAction').value = 'add_trainer';
            document.getElementById('editTrainerId').value = '';
            document.querySelector('#trainerModal form').reset();
            document.getElementById('trainerModal').classList.remove('hidden');
          };
          window.closeModal = function() {
            document.getElementById('trainerModal').classList.add('hidden');
          };
        }

        // Vitals Modal
        if (document.getElementById('vitalsModal')) {
          window.openAddModal = function() {
            document.getElementById('modalTitle').textContent = 'Record Vital Signs';
            document.getElementById('formAction').value = 'add_vitals';
            document.getElementById('editVitalId').value = '';
            document.querySelector('#vitalsModal form').reset();
            if (document.getElementById('date_of_recording')) {
              document.getElementById('date_of_recording').value = (new Date()).toISOString().slice(0,10);
            }
            document.getElementById('vitalsModal').classList.remove('hidden');
          };
          window.closeModal = function() {
            document.getElementById('vitalsModal').classList.add('hidden');
          };
        }

        // Progress Modal
        if (document.getElementById('progressModal')) {
          window.openAddModal = function() {
            document.getElementById('modalTitle').textContent = 'Record Progress';
            document.getElementById('formAction').value = 'add_progress';
            document.getElementById('editProgressId').value = '';
            document.querySelector('#progressModal form').reset();
            if (document.getElementById('progress_date')) {
              document.getElementById('progress_date').value = (new Date()).toISOString().slice(0,10);
            }
            document.getElementById('progressModal').classList.remove('hidden');
          };
          window.closeModal = function() {
            document.getElementById('progressModal').classList.add('hidden');
          };
        }

        // Members Modal (unique function for members.php)
        // Note: This will be overridden by members.php's own function
        if (document.getElementById('memberModal')) {
          window.openMemberAddModal = function() {
            const modal = document.getElementById('memberModal');
            if (modal) {
              modal.classList.add('show');
              modal.style.display = 'flex';
            }
          };
          window.closeMemberModal = function() {
            const modal = document.getElementById('memberModal');
            if (modal) {
              modal.classList.remove('show');
              modal.style.display = 'none';
            }
          };
        }

        // Optional: Close modal on Escape key
        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape') {
            if (typeof closeModal === 'function') closeModal();
          }
        });
      </script>

</body>
</html>
