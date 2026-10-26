<?php
// Public business info. Items marked TODO or SAMPLE must be confirmed before the site goes live.
$site = [
  'name'      => 'DOi PC',
  'full_name' => 'DOi PC Computer Parts & Accessories Trading',
  'tagline'   => 'Computer parts, accessories and warranty-backed custom builds, made from the stock on our shelf.',
  'phone'     => '+63 900 000 0000',                       // TODO
  'whatsapp'  => '+639000000000',                          // TODO (no spaces or dashes)
  'messenger' => 'https://m.me/YOUR_PAGE_USERNAME',        // TODO
  'address'   => 'Magsaysay Ave., Brgy. Dad. South, G.S.C. 9500',
  'hours'     => 'Mon–Sat 9:00 AM – 6:00 PM',              // TODO confirm
  'open'      => 9, 'close' => 18, 'days' => '1,2,3,4,5,6', // "Open now" badge (Mon=1 ... Sun=0)
  'map_embed' => 'https://www.google.com/maps?q=Magsaysay+Ave+Dadiangas+South+General+Santos+City&output=embed', // TODO verify pin
  // SAMPLE claims: replace with real figures, or set to null to hide them
  'trust_badge' => '⭐ Trusted by 500+ Customers',
  'rating_text' => '4.9/5 from 150+ reviews',
];

// SAMPLE figures: replace with real numbers from the owner
$stats = [['Custom builds delivered', 500, '+'], ['Years serving the community', 10, '+'], ['Brands in stock', 10, '+'], ['Warranty on builds', 1, ' yr']];

$brands = ['Ramsta', 'Intel', 'Gigabyte', 'ASUS', 'AMD Radeon', 'PisoFi', 'Orange Pi', 'N-Vision', 'UB', 'MSI'];

// Fallback "starting budget" per build type for the planner widget. When bundles are in stock,
// the real lowest bundle price is used instead (see index.php).
$minimums = ['Office' => 15000, 'Gaming' => 30000, 'Workstation' => 60000];

$services = [
  ['Custom PC builds', 'Parts picked for your budget and workload, assembled and tested in-store.'],
  ['Pre-built systems', 'Ready-to-go desktops for the office, the classroom and the counter.'],
  ['Deep cleaning', 'Overall cleaning of your system unit, inside and out: dust removal, fans, heatsinks and a temperature check.'],
  ['Upgrades', 'More RAM, a faster SSD or a new GPU, fitted while you wait.'],
  ['Repairs', 'Diagnosis and repair for desktops and laptops, with a written estimate first.'],
  ['Parts and accessories', 'CPUs, boards, graphics cards, memory, storage, monitors and peripherals in stock.'],
  ['Supply for shops and offices', 'Bulk pricing for computer shops, resellers and office setups.'],
];

// Text shown on the three category cards. Prices and availability come from the database.
$categoryInfo = [
  'Office'      => ['Fast SSD and 16 GB RAM', 'Quiet, low-power parts', 'Bulk pricing for 5+ units'],
  'Gaming'      => ['Dedicated graphics card', 'Cooling tuned for long sessions', 'Upgrade path built in'],
  'Workstation' => ['High core count CPU', '32 GB+ RAM', 'Built for editing and rendering'],
];

$steps = [
  ['Tell us what you need', 'Share your budget and what you will use the PC for.'],
  ['Choose parts from stock', 'We match your build to parts on the shelf now.'],
  ['We build and test', 'Assembly, cable work and a stress test before handover.'],
  ['Pick up or delivery', 'Collect it in-store or have it delivered, with warranty papers.'],
];

$faqs = [
  ['How long does a custom build take?', 'Most builds are ready within 1–3 days when all parts are in stock.'],
  ['What does the warranty cover?', 'Parts and workmanship. Ask us for the exact terms for each part.'],
  ['Can I bring my own parts?', 'Yes. We can assemble and test parts you already own.'],
  ['What does a deep cleaning include?', 'We clean the dust out of the case, fans and heatsinks, check the inside of the unit and test temperatures afterwards.'],
  ['Do you supply computer shops and offices?', 'Yes. We offer bulk builds and parts supply for resellers and offices.'],
];
