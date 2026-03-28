// API functions
async function fetchAPI(endpoint) {
    try {
        const response = await fetch(`api.php?endpoint=${endpoint}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        return null;
    }
}

// Load about data
async function loadAboutData() {
    console.log('loadAboutData called');
    const aboutContainer = document.getElementById('about-content');
    const loadingElement = document.getElementById('about-loading');
    
    if (!aboutContainer) {
        console.log('about-content container not found');
        return;
    }
    
    console.log('Fetching about data...');
    const data = await fetchAPI('about');
    console.log('About data received:', data);
    if (data && data.success) {
        // Hide loading and show content
        if (loadingElement) loadingElement.style.display = 'none';
        aboutContainer.style.display = 'block';
        
        aboutContainer.innerHTML = `
            <div class="profile-content">
                <div>${data.data.content}</div>
            </div>
        `;
        
        // Load skills
        const skillsContainer = document.getElementById('skills-content');
        if (skillsContainer && data.data.skills) {
            const skills = JSON.parse(data.data.skills);
            let skillsHTML = '<div class="skills-grid">';
            
            for (const [skill, percentage] of Object.entries(skills)) {
                const skillName = skill.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                skillsHTML += `
                    <div class="skill-item">
                        <div class="skill-name">${skillName}</div>
                        <div class="skill-bar">
                            <div class="skill-progress" data-percentage="${percentage}"></div>
                        </div>
                        <div class="skill-percentage">${percentage}%</div>
                    </div>
                `;
            }
            
            skillsHTML += '</div>';
            skillsContainer.innerHTML = skillsHTML;
            
            // Animate skill bars after loading
            setTimeout(animateSkillBars, 500);
        }
    } else {
        // Handle error case
        console.log('Failed to load about data');
        if (loadingElement) loadingElement.style.display = 'none';
        aboutContainer.style.display = 'block';
        aboutContainer.innerHTML = '<div class="error-message">Failed to load profile data. Please try again later.</div>';
    }
}

// Animate skill bars
function animateSkillBars() {
    const skillBars = document.querySelectorAll('.skill-progress');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const percentage = entry.target.dataset.percentage;
                entry.target.style.width = percentage + '%';
            }
        });
    });
    
    skillBars.forEach(bar => {
        observer.observe(bar);
    });
}

// Typewriter effect function
function typeWriter(element, text, speed = 50) {
    console.log('typeWriter called with text:', text);
    let i = 0;
    element.innerHTML = '';
    element.style.borderRight = '2px solid var(--primary-color)';
    
    // Ensure proper text wrapping
    element.style.whiteSpace = 'pre-wrap';
    element.style.wordWrap = 'break-word';
    element.style.lineHeight = '1.4';
    
    function type() {
        if (i < text.length) {
            const char = text.charAt(i);
            element.innerHTML += char;
            i++;
            
            // Adjust speed for natural typing rhythm
            const nextSpeed = char === ' ' ? speed * 0.5 : 
                            char === '\n' ? speed * 2 : 
                            char.match(/[.!?]/) ? speed * 3 : speed;
            
            setTimeout(type, nextSpeed);
        } else {
            console.log('Typewriter animation completed');
            element.style.animation = 'blink-caret 0.75s step-end infinite';
        }
    }
    
    type();
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Script loaded and DOM ready');
    
    // Mobile menu toggle with enhanced animation
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            mobileMenuToggle.classList.toggle('active');
            
            // Add rotation animation to toggle button
            if (navMenu.classList.contains('active')) {
                mobileMenuToggle.style.transform = 'rotate(90deg)';
                document.body.style.overflow = 'hidden'; // Prevent scrolling when menu is open
            } else {
                mobileMenuToggle.style.transform = 'rotate(0deg)';
                document.body.style.overflow = 'auto';
            }
        });
        
        // Close mobile menu when clicking on nav links
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (navMenu.classList.contains('active')) {
                    navMenu.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                    mobileMenuToggle.style.transform = 'rotate(0deg)';
                    document.body.style.overflow = 'auto';
                }
            });
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            const navbar = document.querySelector('.navbar');
            if (navMenu.classList.contains('active') && 
                navbar && !navbar.contains(e.target)) {
                navMenu.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
                mobileMenuToggle.style.transform = 'rotate(0deg)';
                document.body.style.overflow = 'auto';
            }
        });
    }
    
    // Parallax scrolling effect
    function parallaxScroll() {
        const scrolled = window.pageYOffset;
        const parallaxElements = document.querySelectorAll('.parallax');
        
        parallaxElements.forEach(element => {
            const speed = element.dataset.speed || 0.5;
            const yPos = -(scrolled * speed);
            element.style.transform = `translateY(${yPos}px)`;
        });
    }
    
    // Glitch text effect
    function glitchText(element) {
        const text = element.textContent;
        const glitchChars = '!<>-_\\/[]{}—=+*^?#________';
        let iterations = 0;
        
        const interval = setInterval(() => {
            element.textContent = text.split('').map((char, index) => {
                if (index < iterations) {
                    return text[index];
                }
                return glitchChars[Math.floor(Math.random() * glitchChars.length)];
            }).join('');
            
            if (iterations >= text.length) {
                clearInterval(interval);
            }
            
            iterations += 1 / 3;
        }, 30);
    }
    
    // Cursor trail effect
    function createCursorTrail() {
        const trail = [];
        const trailLength = 20;
        
        for (let i = 0; i < trailLength; i++) {
            const dot = document.createElement('div');
            dot.className = 'cursor-trail';
            dot.style.cssText = `
                position: fixed;
                width: 4px;
                height: 4px;
                background: var(--primary-color);
                border-radius: 50%;
                pointer-events: none;
                z-index: 9999;
                opacity: ${1 - i / trailLength};
                transition: all 0.1s ease;
            `;
            document.body.appendChild(dot);
            trail.push(dot);
        }
        
        document.addEventListener('mousemove', (e) => {
            trail.forEach((dot, index) => {
                setTimeout(() => {
                    dot.style.left = e.clientX + 'px';
                    dot.style.top = e.clientY + 'px';
                }, index * 10);
            });
        });
    }
    
    // Terminal typing animation
    function animateTerminalTyping() {
        const terminals = document.querySelectorAll('.terminal');
        
        terminals.forEach(terminal => {
            const content = terminal.querySelector('.terminal-content');
            if (!content) return;
            
            const lines = content.innerHTML.split('\n');
            content.innerHTML = '';
            
            lines.forEach((line, index) => {
                setTimeout(() => {
                    const lineElement = document.createElement('div');
                    lineElement.innerHTML = line;
                    content.appendChild(lineElement);
                    
                    // Add typing sound effect (optional)
                    if (line.includes('$')) {
                        typeWriter(lineElement, line, 50);
                    }
                }, index * 800);
            });
        });
    }
    
    // Initialize typewriter effects with enhanced animations
    console.log('Initializing typewriter effects');
    const typewriterElements = document.querySelectorAll('.typewriter');
    console.log('Found typewriter elements:', typewriterElements.length);
    typewriterElements.forEach((element, index) => {
        const text = element.textContent.trim();
        console.log('Processing element with text:', text);
        if (text) {
            setTimeout(() => {
                typeWriter(element, text, 80);
            }, index * 200);
        }
    });
    
    // Initialize glitch effects on hover
    const glitchElements = document.querySelectorAll('.glitch-text');
    glitchElements.forEach(element => {
        element.addEventListener('mouseenter', () => {
            glitchText(element);
        });
    });
    
    // Initialize scroll-based animations
    window.addEventListener('scroll', () => {
        parallaxScroll();
        updateScrollProgress();
    });
    
    // Scroll progress indicator
    function updateScrollProgress() {
        const scrollProgress = document.querySelector('.scroll-progress');
        if (scrollProgress) {
            const scrollTop = document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const progress = (scrollTop / scrollHeight) * 100;
            scrollProgress.style.width = progress + '%';
        }
    }
    
    // Create cursor trail
    createCursorTrail();
    
    // Animate terminals on page load
    setTimeout(animateTerminalTyping, 1000);
    

    
    // Terminal command simulation
    function simulateTerminalCommand(terminalElement, commands, delay = 1000) {
        const content = terminalElement.querySelector('.terminal-content');
        let commandIndex = 0;
        
        function executeCommand() {
            if (commandIndex < commands.length) {
                const command = commands[commandIndex];
                const commandLine = document.createElement('div');
                commandLine.innerHTML = `<span class="prompt">serein@security:~$</span> ${command.input}`;
                content.appendChild(commandLine);
                
                setTimeout(() => {
                    if (command.output) {
                        const outputLine = document.createElement('div');
                        outputLine.innerHTML = command.output;
                        content.appendChild(outputLine);
                    }
                    commandIndex++;
                    setTimeout(executeCommand, delay);
                }, 500);
            }
        }
        
        executeCommand();
    }
    
    // Animate skill bars

    
    // Fade in animation on scroll
    function fadeInOnScroll() {
        const elements = document.querySelectorAll('.fade-in-up');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        });
        
        elements.forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            element.style.transition = 'all 0.6s ease';
            observer.observe(element);
        });
    }
    
    // API functions

    

    
    // Load blog posts
    async function loadBlogPosts() {
        const blogContainer = document.getElementById('blog-posts');
        if (!blogContainer) return;
        
        const data = await fetchAPI('articles');
        if (data && data.success) {
            let postsHTML = '';
            
            data.data.forEach(post => {
                const excerpt = (post.content || '').replace(/<[^>]*>/g, '').substring(0, 200) + '...';
                const date = new Date(post.created_at).toLocaleDateString();
                
                postsHTML += `
                    <article class="post-item fade-in-up">
                        <h2><a href="post.html?slug=${post.slug}" class="post-title">${post.title}</a></h2>
                        <div class="post-meta">Published on ${date}</div>
                        <div class="post-excerpt">${excerpt}</div>
                        <a href="post.html?slug=${post.slug}" class="btn">Read More</a>
                    </article>
                `;
            });
            
            blogContainer.innerHTML = postsHTML;
            fadeInOnScroll();
        }
    }
    
    // Load single post
    async function loadSinglePost() {
        const postContainer = document.getElementById('post-content');
        if (!postContainer) return;
        
        const urlParams = new URLSearchParams(window.location.search);
        const slug = urlParams.get('slug');
        
        if (!slug) {
            postContainer.innerHTML = '<div class="card"><h2>Post not found</h2></div>';
            return;
        }
        
        const data = await fetchAPI(`article&slug=${slug}`);
        if (data && data.success && data.data) {
            const post = data.data;
            const date = new Date(post.created_at).toLocaleDateString();
            
            postContainer.innerHTML = `
                <article class="card">
                    <h1 class="card-title">${post.title}</h1>
                    <div class="post-meta">Published on ${date}</div>
                    <div class="card-content">${post.content}</div>
                </article>
            `;
        } else {
            postContainer.innerHTML = '<div class="card"><h2>Post not found</h2></div>';
        }
    }
    
// Load projects
async function loadProjects() {
    const projectsContainer = document.getElementById('projects-content');
    if (!projectsContainer) return;
    
    const data = await fetchAPI('projects');
    if (data && data.success) {
        let projectsHTML = '<div class="grid grid-2">';
        
        data.data.forEach(project => {
            projectsHTML += `
                <div class="card fade-in-up">
                    <h3 class="card-title">${project.title}</h3>
                    <div class="card-content">
                        <p>${project.description}</p>
                        ${project.link ? `<a href="${project.link}" class="btn" target="_blank">View Project</a>` : ''}
                    </div>
                </div>
            `;
        });
        
        projectsHTML += '</div>';
        projectsContainer.innerHTML = projectsHTML;
        fadeInOnScroll();
    }
}
    
    // Contact form handler
    function handleContactForm() {
        const contactForm = document.getElementById('contact-form');
        if (!contactForm) return;
        
        contactForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(contactForm);
            const submitBtn = contactForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.textContent = 'Sending...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Message sent successfully!');
                    contactForm.reset();
                } else {
                    alert('Error sending message: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error sending message. Please try again.');
            } finally {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
    }
    
    // Load SEO data
    async function loadSEOData() {
        const currentPage = window.location.pathname.split('/').pop().replace('.html', '') || 'home';
        
        const data = await fetchAPI(`seo&page=${currentPage}`);
        if (data && data.success && data.data) {
            const seo = data.data;
            
            document.title = seo.title;
            
            let metaDescription = document.querySelector('meta[name="description"]');
            if (!metaDescription) {
                metaDescription = document.createElement('meta');
                metaDescription.name = 'description';
                document.head.appendChild(metaDescription);
            }
            metaDescription.content = seo.description;
            
            let metaKeywords = document.querySelector('meta[name="keywords"]');
            if (!metaKeywords) {
                metaKeywords = document.createElement('meta');
                metaKeywords.name = 'keywords';
                document.head.appendChild(metaKeywords);
            }
            metaKeywords.content = seo.keywords;
        }
    }
    
    // Enhanced Matrix Rain Effect with Performance Optimization
    function createMatrixRain() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.id = 'matrix-canvas';
        canvas.style.position = 'fixed';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.style.pointerEvents = 'none';
        canvas.style.zIndex = '-1';
        canvas.style.opacity = '0.15';
        canvas.style.mixBlendMode = 'screen';
        
        document.body.appendChild(canvas);
        
        let animationId;
        let isVisible = true;
        
        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            initializeDrops();
        }
        
        const matrix = "ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789@#$%^&*()*&^%+-/~{[|`]}";
        const matrixArray = matrix.split("");
        
        const fontSize = window.innerWidth <= 768 ? 8 : 12;
        let columns, drops;
        
        function initializeDrops() {
            columns = Math.floor(canvas.width / fontSize);
            drops = [];
            
            for (let x = 0; x < columns; x++) {
                drops[x] = Math.floor(Math.random() * canvas.height / fontSize);
            }
        }
        
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
        
        // Pause animation when tab is not visible
        document.addEventListener('visibilitychange', () => {
            isVisible = !document.hidden;
            if (isVisible) {
                animate();
            } else {
                cancelAnimationFrame(animationId);
            }
        });
        
        function draw() {
            // Create fade effect
            ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // Set text properties
            ctx.fillStyle = '#00ff41';
            ctx.font = fontSize + 'px "Courier New", monospace';
            ctx.textAlign = 'center';
            
            // Draw matrix characters
            for (let i = 0; i < drops.length; i++) {
                // Add glow effect for some characters
                if (Math.random() > 0.98) {
                    ctx.shadowColor = '#00ff41';
                    ctx.shadowBlur = 10;
                } else {
                    ctx.shadowBlur = 0;
                }
                
                const text = matrixArray[Math.floor(Math.random() * matrixArray.length)];
                const x = i * fontSize + fontSize / 2;
                const y = drops[i] * fontSize;
                
                ctx.fillText(text, x, y);
                
                // Reset drop when it reaches bottom
                if (y > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }
                drops[i]++;
            }
            
            ctx.shadowBlur = 0;
        }
        
        function animate() {
            if (isVisible) {
                draw();
                animationId = requestAnimationFrame(animate);
            }
        }
        
        animate();
        
        // Return control functions
        return {
            pause: () => {
                isVisible = false;
                cancelAnimationFrame(animationId);
            },
            resume: () => {
                isVisible = true;
                animate();
            },
            destroy: () => {
                cancelAnimationFrame(animationId);
                canvas.remove();
            }
        };
    }
    
    // Enhanced Particle System with Better Performance
    function createParticleSystem() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.id = 'particle-canvas';
        canvas.style.position = 'fixed';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.style.pointerEvents = 'none';
        canvas.style.zIndex = '-2';
        canvas.style.opacity = '0.6';
        
        document.body.appendChild(canvas);
        
        let animationId;
        let isVisible = true;
        const particles = [];
        
        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
        
        // Particle class
        class Particle {
            constructor() {
                this.reset();
                this.y = Math.random() * canvas.height;
            }
            
            reset() {
                this.x = Math.random() * canvas.width;
                this.y = -10;
                this.size = Math.random() * 2 + 0.5;
                this.speed = Math.random() * 2 + 0.5;
                this.opacity = Math.random() * 0.5 + 0.3;
                this.drift = (Math.random() - 0.5) * 0.5;
            }
            
            update() {
                this.y += this.speed;
                this.x += this.drift;
                
                // Reset particle when it goes off screen
                if (this.y > canvas.height + 10 || this.x < -10 || this.x > canvas.width + 10) {
                    this.reset();
                }
            }
            
            draw() {
                ctx.save();
                ctx.globalAlpha = this.opacity;
                ctx.fillStyle = '#00ff41';
                ctx.shadowColor = '#00ff41';
                ctx.shadowBlur = this.size * 2;
                
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
                
                ctx.restore();
            }
        }
        
        // Create particles
        const particleCount = window.innerWidth <= 768 ? 20 : 40;
        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }
        
        // Pause animation when tab is not visible
        document.addEventListener('visibilitychange', () => {
            isVisible = !document.hidden;
            if (isVisible) {
                animate();
            } else {
                cancelAnimationFrame(animationId);
            }
        });
        
        function animate() {
            if (!isVisible) return;
            
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Update and draw particles
            particles.forEach(particle => {
                particle.update();
                particle.draw();
            });
            
            animationId = requestAnimationFrame(animate);
        }
        
        animate();
        
        // Return control functions
        return {
            pause: () => {
                isVisible = false;
                cancelAnimationFrame(animationId);
            },
            resume: () => {
                isVisible = true;
                animate();
            },
            destroy: () => {
                cancelAnimationFrame(animationId);
                canvas.remove();
            }
        };
    }
    
    // Enhanced touch and swipe support for mobile
    function addTouchSupport() {
        let startY = 0;
        let startX = 0;
        let isScrolling = false;
        
        // Improve touch scrolling performance
        document.addEventListener('touchstart', (e) => {
            startY = e.touches[0].clientY;
            startX = e.touches[0].clientX;
            isScrolling = false;
        }, { passive: true });
        
        document.addEventListener('touchmove', (e) => {
            if (!startY || !startX) return;
            
            const currentY = e.touches[0].clientY;
            const currentX = e.touches[0].clientX;
            const diffY = startY - currentY;
            const diffX = startX - currentX;
            
            // Determine if user is scrolling
            if (Math.abs(diffY) > Math.abs(diffX)) {
                isScrolling = true;
            }
            
            // Horizontal swipe detection (only if not scrolling)
            if (!isScrolling && Math.abs(diffX) > Math.abs(diffY)) {
                if (diffX > 50) {
                    // Swipe left - close mobile menu if open
                    const navMenu = document.querySelector('.nav-menu');
                    const mobileToggle = document.querySelector('.mobile-menu-toggle');
                    if (navMenu && navMenu.classList.contains('active')) {
                        navMenu.classList.remove('active');
                        mobileToggle.classList.remove('active');
                        mobileToggle.style.transform = 'rotate(0deg)';
                        document.body.style.overflow = 'auto';
                    }
                } else if (diffX < -50) {
                    // Swipe right - could open mobile menu or navigate back
                    console.log('Swipe right detected');
                }
            }
        }, { passive: true });
        
        document.addEventListener('touchend', () => {
            startY = 0;
            startX = 0;
            isScrolling = false;
        }, { passive: true });
    }
    
    // Optimize animations for mobile devices
    function optimizeForMobile() {
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            // Disable heavy animations on mobile
            document.body.classList.add('mobile-device');
            
            // Disable parallax on mobile for better performance
            const parallaxElements = document.querySelectorAll('.parallax');
            parallaxElements.forEach(el => {
                el.style.transform = 'none';
            });
        }
    }
    
    // Handle orientation change
    function handleOrientationChange() {
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                // Recalculate dimensions after orientation change
                window.scrollTo(0, 0);
                
                // Reinitialize certain components if needed
                const navMenu = document.querySelector('.nav-menu');
                const mobileToggle = document.querySelector('.mobile-menu-toggle');
                if (navMenu && navMenu.classList.contains('active')) {
                    navMenu.classList.remove('active');
                    mobileToggle.classList.remove('active');
                    mobileToggle.style.transform = 'rotate(0deg)';
                    document.body.style.overflow = 'auto';
                }
                
                // Trigger resize event
                window.dispatchEvent(new Event('resize'));
            }, 100);
        });
    }
    
    // Initialize everything
    loadSEOData();
    loadAboutData();
    loadBlogPosts();
    loadSinglePost();
    loadProjects();
    handleContactForm();
    fadeInOnScroll();
    animateSkillBars();
    
    // Initialize mobile enhancements
    addTouchSupport();
    optimizeForMobile();
    handleOrientationChange();
    
    // Initialize enhanced visual effects with performance monitoring
    let matrixRain, particleSystem;
    
    // Only create effects if device can handle them
    const canHandleEffects = !window.matchMedia('(prefers-reduced-motion: reduce)').matches && 
                            window.innerWidth > 480 && 
                            !navigator.userAgent.includes('Mobile');
    
    if (canHandleEffects) {
        matrixRain = createMatrixRain();
        particleSystem = createParticleSystem();
        
        // Pause effects when page is not visible to save resources
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                matrixRain?.pause();
                particleSystem?.pause();
            } else {
                matrixRain?.resume();
                particleSystem?.resume();
            }
        });
    }
    
    // Clean up on page unload
    window.addEventListener('beforeunload', () => {
        matrixRain?.destroy();
        particleSystem?.destroy();
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add active class to current nav item
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage || 
            (currentPage === '' && link.getAttribute('href') === 'index.html')) {
            link.classList.add('active');
        }
    });
});