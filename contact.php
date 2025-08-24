<?php
require_once __DIR__ . '/app/bootstrap.php';
include __DIR__ . '/app/views/partials/header.php';
?>

<!-- Contact Page Content -->
<section class="max-w-7xl mx-auto px-4 py-12">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-extrabold">Contact Us</h2>
    </div>

    <!-- Contact Information -->
    <div class="bg-white rounded-2xl shadow p-8">
        <h3 class="text-2xl font-bold mb-6">Get in Touch</h3>
        <p class="text-lg mb-4">If you have any questions, concerns, or need assistance, feel free to contact us. We're always here to help!</p>
        
        <div class="text-lg mb-4">
            <strong>Email:</strong> letters@comarketplace.eu
        </div>

        <div class="text-lg">
            You can also follow us on our social media platforms:
        </div>
        <ul class="list-disc pl-6 mt-4">
            <li><a href="https://facebook.com/yourpage" class="text-blue-500">Facebook</a></li>
        </ul>
    </div>
</section>

<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
