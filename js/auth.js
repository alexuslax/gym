document.addEventListener('DOMContentLoaded', () => {
  console.log('Auth.js loaded successfully'); // Debug log

  const formPanel = document.querySelector('.form-panel');
  const showSignupBtn = document.getElementById('show-signup');
  const roleSelection = document.getElementById('role-selection');
  const signupFormStudent = document.getElementById('signup-form-student');
  const signupFormFaculty = document.getElementById('signup-form-faculty');
  const loginForm = document.getElementById('login-form');
  const signupPrompt = showSignupBtn ? showSignupBtn.parentElement : null;

  // Debug: Log element status
  console.log('Elements found:', {
    formPanel: !!formPanel,
    showSignupBtn: !!showSignupBtn,
    roleSelection: !!roleSelection,
    signupFormStudent: !!signupFormStudent,
    signupFormFaculty: !!signupFormFaculty,
    loginForm: !!loginForm
  });

  // Setup profile picture preview handler
  function setupProfilePicturePreview(picInputId, previewImageId, noFileTextId) {
    const picInput = document.getElementById(picInputId);
    const previewImage = document.getElementById(previewImageId);
    const noFileText = document.getElementById(noFileTextId);
    const defaultImage = 'img/image.png';

    if (previewImage) {
      previewImage.src = defaultImage;
      previewImage.classList.remove('hidden');
    }
    if (noFileText) {
      noFileText.classList.remove('hidden');
    }

    if (picInput && previewImage && noFileText) {
      picInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = (event) => {
            previewImage.src = event.target.result;
            previewImage.classList.remove('hidden');
            noFileText.classList.add('hidden');
          };
          reader.readAsDataURL(file);
        } else {
          previewImage.src = defaultImage;
          previewImage.classList.remove('hidden');
          noFileText.classList.remove('hidden');
        }
      });
    }
  }

  // Initialize preview handlers
  setupProfilePicturePreview('signup_picture', 'preview-image', 'no-file-text');
  setupProfilePicturePreview('signup_picture_faculty', 'preview-image-faculty', 'no-file-text-faculty');
  setupProfilePicturePreview('signup_picture_staff', 'preview-image-staff', 'no-file-text-staff');

  // Check if we should show signup form from URL parameter
  const urlParams = new URLSearchParams(window.location.search);
  const formType = urlParams.get('form');
  const signupType = urlParams.get('type');
  
  if (formType === 'signup') {
    if (loginForm) loginForm.classList.add('hidden');
    if (signupPrompt) signupPrompt.classList.add('hidden');
    
    if (signupType === 'faculty') {
      if (roleSelection) roleSelection.classList.add('hidden');
      if (signupFormFaculty) signupFormFaculty.classList.remove('hidden');
    } else if (signupType === 'student') {
      if (roleSelection) roleSelection.classList.add('hidden');
      if (signupFormStudent) signupFormStudent.classList.remove('hidden');
    } else {
      if (roleSelection) roleSelection.classList.remove('hidden');
    }
  }

  // Show role selection when Sign Up is clicked
  if (showSignupBtn) {
    showSignupBtn.addEventListener('click', (e) => {
      e.preventDefault();
      console.log('Sign up button clicked'); // Debug log
      
      if (loginForm) loginForm.classList.add('hidden');
      if (signupPrompt) signupPrompt.classList.add('hidden');
      if (signupFormStudent) signupFormStudent.classList.add('hidden');
      if (signupFormFaculty) signupFormFaculty.classList.add('hidden');
      
      if (roleSelection) {
        roleSelection.classList.remove('hidden');
        console.log('Role selection should be visible now'); // Debug log
      } else {
        console.error('Role selection element not found!'); // Debug log
      }
      
      // Hide error message when switching to signup
      const errorMessage = document.getElementById('error-message');
      if (errorMessage) {
        errorMessage.classList.add('hidden');
      }
    });
  } else {
    console.error('Show signup button not found!'); // Debug log
  }

  // Select Student
  const selectStudentBtn = document.getElementById('select-student');
  if (selectStudentBtn) {
    selectStudentBtn.addEventListener('click', () => {
      console.log('Student button clicked!'); // Debug
      console.log('roleSelection:', roleSelection);
      console.log('signupFormStudent:', signupFormStudent);
      console.log('signupFormFaculty:', signupFormFaculty);
      
      roleSelection.classList.add('hidden');
      signupFormFaculty.classList.add('hidden');
      signupFormStudent.classList.remove('hidden');
      
      console.log('Student form should be visible now');
    });
  } else {
    console.error('Student button not found!');
  }

  // Select Faculty
  const selectFacultyBtn = document.getElementById('select-faculty');
  if (selectFacultyBtn) {
    selectFacultyBtn.addEventListener('click', () => {
      console.log('Faculty button clicked!'); // Debug
      console.log('roleSelection:', roleSelection);
      console.log('signupFormFaculty:', signupFormFaculty);
      console.log('signupFormStudent:', signupFormStudent);
      
      roleSelection.classList.add('hidden');
      signupFormStudent.classList.add('hidden');
      signupFormFaculty.classList.remove('hidden');
      
      console.log('Faculty form should be visible now');
    });
  } else {
    console.error('Faculty button not found!');
  }

  // Select Staff
  const selectStaffBtn = document.getElementById('select-staff');
  const signupFormStaff = document.getElementById('signup-form-staff');
  if (selectStaffBtn) {
    selectStaffBtn.addEventListener('click', () => {
      console.log('Staff button clicked!'); // Debug
      console.log('roleSelection:', roleSelection);
      console.log('signupFormStaff:', signupFormStaff);

      if (roleSelection) roleSelection.classList.add('hidden');
      if (signupFormStudent) signupFormStudent.classList.add('hidden');
      if (signupFormFaculty) signupFormFaculty.classList.add('hidden');
      if (signupFormStaff) signupFormStaff.classList.remove('hidden');

      console.log('Staff form should be visible now');
    });
  } else {
    console.error('Staff button not found!');
  }

  // Back to role selection from student form
  const backToRoleStudentBtn = document.getElementById('back-to-role-student');
  if (backToRoleStudentBtn) {
    backToRoleStudentBtn.addEventListener('click', () => {
      signupFormStudent.classList.add('hidden');
      roleSelection.classList.remove('hidden');
    });
  }

  // Back to role selection from faculty form
  const backToRoleFacultyBtn = document.getElementById('back-to-role-faculty');
  if (backToRoleFacultyBtn) {
    backToRoleFacultyBtn.addEventListener('click', () => {
      signupFormFaculty.classList.add('hidden');
      roleSelection.classList.remove('hidden');
    });
  }

  // Back to role selection from staff form
  const backToRoleStaffBtn = document.getElementById('back-to-role-staff');
  if (backToRoleStaffBtn) {
    backToRoleStaffBtn.addEventListener('click', () => {
      if (signupFormStaff) signupFormStaff.classList.add('hidden');
      if (roleSelection) roleSelection.classList.remove('hidden');
    });
  }

  // Back to Login from role selection
  const backToLoginFromRoleBtn = document.getElementById('back-to-login-from-role');
  if (backToLoginFromRoleBtn) {
    backToLoginFromRoleBtn.addEventListener('click', () => {
      roleSelection.classList.add('hidden');
      signupFormStudent.classList.add('hidden');
      signupFormFaculty.classList.add('hidden');
      loginForm.classList.remove('hidden');
      signupPrompt.classList.remove('hidden');
      
      // Hide error message when switching back to login
      const errorMessage = document.getElementById('error-message');
      if (errorMessage) {
        errorMessage.classList.add('hidden');
      }
    });
  }

  // Back to Login from student form
  const backToLoginStudentBtn = document.getElementById('back-to-login-student');
  if (backToLoginStudentBtn) {
    backToLoginStudentBtn.addEventListener('click', () => {
      signupFormStudent.classList.add('hidden');
      roleSelection.classList.add('hidden');
      loginForm.classList.remove('hidden');
      signupPrompt.classList.remove('hidden');
      
      // Hide error message when switching back to login
      const errorMessage = document.getElementById('error-message');
      if (errorMessage) {
        errorMessage.classList.add('hidden');
      }
    });
  }

  // Back to Login from faculty form
  const backToLoginFacultyBtn = document.getElementById('back-to-login-faculty');
  if (backToLoginFacultyBtn) {
    backToLoginFacultyBtn.addEventListener('click', () => {
      signupFormFaculty.classList.add('hidden');
      roleSelection.classList.add('hidden');
      loginForm.classList.remove('hidden');
      signupPrompt.classList.remove('hidden');
      
      // Hide error message when switching back to login
      const errorMessage = document.getElementById('error-message');
      if (errorMessage) {
        errorMessage.classList.add('hidden');
      }
    });
  }

  // Back to Login from staff form
  const backToLoginStaffBtn = document.getElementById('back-to-login-staff');
  if (backToLoginStaffBtn) {
    backToLoginStaffBtn.addEventListener('click', () => {
      if (signupFormStaff) signupFormStaff.classList.add('hidden');
      if (roleSelection) roleSelection.classList.add('hidden');
      if (loginForm) loginForm.classList.remove('hidden');
      if (signupPrompt) signupPrompt.classList.remove('hidden');

      // Hide error message when switching back to login
      const errorMessage = document.getElementById('error-message');
      if (errorMessage) {
        errorMessage.classList.add('hidden');
      }
    });
  }

  // Password toggle function
  function setupTogglePassword(inputId, toggleId) {
    const input = document.getElementById(inputId);
    const toggle = document.getElementById(toggleId);
    if (!input || !toggle) return;
    let shown = false;
    toggle.addEventListener('click', (e) => {
      e.preventDefault();
      shown = !shown;
      input.type = shown ? 'text' : 'password';
      const img = toggle.querySelector('img');
      if (img) img.src = shown ? 'img/hide.png' : 'img/show.png';
    });
  }

  setupTogglePassword('login-password', 'toggle-login-password');
  setupTogglePassword('signup-password', 'toggle-signup-password');
  setupTogglePassword('signup-confirm-password', 'toggle-signup-confirm-password');
  setupTogglePassword('signup-password-faculty', 'toggle-signup-password-faculty');
  setupTogglePassword('signup-confirm-password-faculty', 'toggle-signup-confirm-password-faculty');
  setupTogglePassword('signup-password-staff', 'toggle-signup-password-staff');
  setupTogglePassword('signup-confirm-password-staff', 'toggle-signup-confirm-password-staff');

});
