<?php
/*
 *  footer.php
 */
?>

<footer class="footer">
    <div class="footer-content">
        <p>© 2025 - <a href="https://www.itssmart.it/" class="footer-link">ITS Smart Academy</a> | <a href="#" class="footer-link">Privacy Policy</a></p>
    </div>
</footer>

<style>
/* Stili per il footer */
.footer {
    position: absolute;
    bottom: 0;
    width: 100%;
    height: 60px; 
    background: linear-gradient(270deg, #34abe3, #6f4084, #57b286);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0px -2px 4px rgba(0,0,0,0.2);
    color: #fff;
    text-align: center;
    z-index: 10; 
}


.footer-content {
    width: 100%;
    max-width: 800px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}


.footer-content p {
    margin: 5px 0;
    font-weight: 600;
}


.footer-link {
    color: #ffffff;
    text-decoration: none;
    font-weight: 700;
    transition: color 0.3s;
}

.footer-link:hover {
    color: #7C78B8;
}

.dark-mode .footer {
    background: #222; 
    color: #f1f1f1;
    box-shadow: 0px -2px 4px rgba(255,255,255,0.2);
}


.dark-mode .footer-link:hover {
    color: #999;
}
</style>
