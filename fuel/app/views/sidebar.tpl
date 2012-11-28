

            <!-- # Sidebar -->
            <aside id="sidebar">
                <!-- Logo -->
                <div id="logo">
                    <img src="/assets/img/logo-small.png" alt="Esplendido - Logo" />
                </div>
                <!-- /Logo -->

                <!-- Me bar -->
                <div id="me" class="secondary-widget">
                    <figure>
                        <img src="/assets/img/me.png" alt="Sterling - Avatar" />
                    </figure>
                    <div>
                        <h1>Sterling</h1>
                        <span>Administrator</span>
                        <ul>
                            <li><a href="#" title="Edit profile">edit profile</a></li>
                            <li><a href="login.html" title="Logout">logout</a></li>
                        </ul>
                    </div>
                </div>
                <!-- /Me bar -->

                <!-- Search -->
                <div class="search primary-widget">
                    <input type="text" placeholder="Search for" />
                    <select>
                        <option>everything</option>
                        <option>products</option>
                        <option>posts</option>
                        <option>users</option>
                        <option>media</option>
                    </select>
                </div>
                <!-- /Search -->

                <!-- Menu -->
                <div class="menu primary-widget">
                    <nav>
                        <ul>
                            <li {php}echo Uri::segment(1) == "Dashboard" ? 'class="active"' : ''{/php}>
                                <a href="/dashboard" title="Dashboard" data-icon="cloud">
                                    Dashboard
                                </a>
                            </li>
                            <li {php}echo Uri::segment(1) == "Deployment" ? 'class="active"' : ''{/php}>
                                <a href="/Deployment" title="Projects" data-icon="zoom-in">
                                    NEW DEPLOYMENT
                                </a>
                            </li>
                            <li class="with-submenu" {php}echo Uri::segment(1) == "Projects" ? 'class="active"' : ''{/php}>
                                <a href="#" title="UI Elements" data-icon="folder">
                                    Projects
                                </a>
                                <nav>
                                    <ul>
                                        <li><a href="widgets.html" title="List all projects">List all projects</a></li>
                                        <li><a href="icons.html" title="Add a new project">Add a new project</a></li>
                                    </ul>
                                </nav>
                            </li>
                            <li class="with-submenu" {php}echo Uri::segment(1) == "Projects" ? 'class="active"' : ''{/php}>
                                <a href="#" title="UI Elements" data-icon="user">
                                    Users
                                </a>
                                <nav>
                                    <ul>
                                        <li><a href="widgets.html" title="List all projects">List all users</a></li>
                                        <li><a href="icons.html" title="Add a new project">Add a new user</a></li>
                                    </ul>
                                </nav>
                            </li>
                           
                            <li>
                                <a href="form.html" title="Form" data-icon="rotate">
                                    Queues
                                </a>
                            </li>
                           
                            
                        </ul>
                    </nav>
                </div>
                <!-- /Menu -->

                <!-- Widget 1 -->
                <div class="primary-widget">
                    <p>Something here, if you like.</p>
                </div>
                <!-- /Widget 1 -->
            </aside>
            <!-- /Sidebar -->