#!/bin/bash

echo "🔑 Password Reset Integration Test"
echo "================================="

cd /var/www/html/homesite

# Test 1: Login with current credentials
echo "Test 1: Login with current password..."
LOGIN_TOKEN=$(curl -s http://localhost/admin/login.php | grep -o 'csrf_token" value="[^"]*"' | cut -d'"' -f3)
LOGIN_RESULT=$(curl -s -c test_session.txt -X POST http://localhost/admin/login.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "username=cadman_admin&password=cadman123&csrf_token=$LOGIN_TOKEN&login=1" \
  -w "%{http_code}")

if [[ $LOGIN_RESULT == *"302"* ]] || curl -s -b test_session.txt http://localhost/admin/ | grep -q "Admin Portal"; then
    echo "✅ Current password works"
else
    echo "❌ Current password failed"
    exit 1
fi

# Test 2: Get CSRF token for password reset
echo "Test 2: Getting password reset form..."
RESET_TOKEN=$(curl -s -b test_session.txt http://localhost/admin/password_reset.php | grep -o 'csrf_token" value="[^"]*"' | cut -d'"' -f3)

if [ -n "$RESET_TOKEN" ]; then
    echo "✅ Got CSRF token: ${RESET_TOKEN:0:20}..."
else
    echo "❌ Failed to get CSRF token"
    exit 1
fi

# Test 3: Change password
echo "Test 3: Changing password..."
NEW_PASSWORD="TestPassword456!"
RESET_RESULT=$(curl -s -b test_session.txt -c test_session.txt -X POST http://localhost/admin/password_reset.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "step=change&current_password=cadman123&new_password=$NEW_PASSWORD&confirm_password=$NEW_PASSWORD&csrf_token=$RESET_TOKEN")

if echo "$RESET_RESULT" | grep -q "successfully changed"; then
    echo "✅ Password change reported success"
else
    echo "❌ Password change failed"
    echo "Response: $(echo "$RESET_RESULT" | grep -A 5 -B 5 "error\|success")"
    exit 1
fi

# Test 4: Try logging in with new password
echo "Test 4: Testing new password..."
sleep 2  # Brief pause to ensure changes are written

NEW_LOGIN_TOKEN=$(curl -s http://localhost/admin/login.php | grep -o 'csrf_token" value="[^"]*"' | cut -d'"' -f3)
NEW_LOGIN_RESULT=$(curl -s -c test_session2.txt -X POST http://localhost/admin/login.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "username=cadman_admin&password=$NEW_PASSWORD&csrf_token=$NEW_LOGIN_TOKEN&login=1" \
  -w "%{http_code}")

if [[ $NEW_LOGIN_RESULT == *"302"* ]] || curl -s -b test_session2.txt http://localhost/admin/ | grep -q "Admin Portal"; then
    echo "✅ New password works!"
else
    echo "❌ New password doesn't work"
    echo "Trying to login again..."
    # Try old password to see if change actually happened
    OLD_LOGIN_RESULT=$(curl -s -c test_session3.txt -X POST http://localhost/admin/login.php \
      -H "Content-Type: application/x-www-form-urlencoded" \
      -d "username=cadman_admin&password=cadman123&csrf_token=$NEW_LOGIN_TOKEN&login=1" \
      -w "%{http_code}")
    
    if [[ $OLD_LOGIN_RESULT == *"302"* ]] || curl -s -b test_session3.txt http://localhost/admin/ | grep -q "Admin Portal"; then
        echo "❌ Old password still works - change didn't take effect"
    else
        echo "❌ Neither old nor new password works - something went wrong"
    fi
    exit 1
fi

# Test 5: Restore original password for safety
echo "Test 5: Restoring original password..."
RESTORE_TOKEN=$(curl -s -b test_session2.txt http://localhost/admin/password_reset.php | grep -o 'csrf_token" value="[^"]*"' | cut -d'"' -f3)
RESTORE_RESULT=$(curl -s -b test_session2.txt -X POST http://localhost/admin/password_reset.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "step=change&current_password=$NEW_PASSWORD&new_password=cadman123&confirm_password=cadman123&csrf_token=$RESTORE_TOKEN")

if echo "$RESTORE_RESULT" | grep -q "successfully changed"; then
    echo "✅ Original password restored"
else
    echo "⚠️  Failed to restore original password - manual intervention needed"
fi

# Cleanup
rm -f test_session*.txt

echo ""
echo "🎉 Password Reset Test Complete!"
echo "The password reset functionality is working properly."