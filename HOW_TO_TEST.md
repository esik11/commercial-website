# How to Run Security Tests

## Quick Start

1. Make sure your server is running (XAMPP or PHP dev server)

2. Run the test:
   ```bash
   php final_test.php
   ```

3. Check the results in the terminal

4. Review detailed results in `TEST_RESULTS_FINAL.md`

## What Gets Tested

- ✅ CSRF token validation
- ✅ Honeypot spam protection
- ✅ Rate limiting (30-second and IP-based)
- ✅ Input validation
- ✅ Login brute-force protection
- ✅ Protected file access
- ✅ PHP execution blocking in uploads

## Manual Tests Still Needed

1. **File Upload Security** - Test in admin panel:
   - Try uploading .php file (should reject)
   - Try uploading 3MB+ file (should reject)
   - Try fake image file (should reject)
   - Upload real images (should work)

2. **Bundle Visibility** - In admin:
   - Set a part's stock to 0
   - Check homepage (bundle should disappear)
   - Restock the part
   - Check homepage (bundle should reappear)

3. **Stock Deduction** - In admin quotes:
   - Mark a bundle quote as "Converted"
   - Check stock decreased
   - Try marking same quote "Converted" again
   - Stock should NOT decrease again

## Known Issues

- **Test 2 fails:** CSRF validation may have a bypass (returns 200 instead of 403)
- **Tests 11-12:** Database schema doesn't match (different table/column names)

## Files

- `final_test.php` - The test script
- `TEST_RESULTS_FINAL.md` - Detailed results
- This file - Instructions
