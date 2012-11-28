<!DOCTYPE html>
{literal}
<html>
    <head>
        <meta charset="UTF-8" />
        <title>Esplendido! - Premium Admin Panel</title>
        
        <!-- CSS -->
        <link rel="stylesheet" href="/assets/css/reset.css" />
        <link rel="stylesheet" href="/assets/css/grid-fluid.css" />
        <link rel="stylesheet" href="/assets/css/websymbols.css" />
        <link rel="stylesheet" href="/assets/css/formalize.css" />
        <link rel="stylesheet" href="/assets/css/esplendido.css" />
        <link rel="stylesheet" href="/assets/css/light.css" />
        <link rel="stylesheet" href="/assets/plugins/chosen/chosen.css" />
        <link rel="stylesheet" href="/assets/plugins/ui/ui-custom.css" />
        <link rel="stylesheet" href="/assets/plugins/tipsy/tipsy.css" />
        <link rel="stylesheet" href="/assets/plugins/validationEngine/validationEngine.jquery.css" />
        <link rel="stylesheet" href="/assets/plugins/elrte/css/elrte.min.css" />
        <link rel="stylesheet" href="/assets/plugins/miniColors/jquery.miniColors.css" />
        <link rel="stylesheet" href="/assets/plugins/fullCalendar/fullcalendar.css" />
        <link rel="stylesheet" href="/assets/plugins/elfinder/css/elfinder.css" />
        <link rel="stylesheet" href="/assets/plugins/shadowbox/shadowbox.css" />

        <!-- JAVASCRIPTs -->
        <!--[if lt IE 9]>
            <script language="javascript" type="text/javascript" src="/assets/plugins/jqPlot/excanvas.min.js"></script>
            <script language="javascript" type="text/javascript" src="/assets/js/html5shiv.js"></script>
        <![endif]-->
        <script src="/assets/js/jquery.js"></script>
        <script src="/assets/js/esplendido.js"></script>
        <script src="/assets/js/browserDetect.js"></script>
        <script src="/assets/js/jquery.formalize.min.js"></script>
        <script src="/assets/plugins/prefixfree.min.js"></script>
        <script src="/assets/plugins/jquery.uniform.min.js"></script>
        <script src="/assets/plugins/chosen/chosen.jquery.min.js"></script>
        <script src="/assets/plugins/ui/ui-custom.js"></script>
        <script src="/assets/plugins/ui/multiselect/js/ui.multiselect.js"></script>
        <script src="/assets/plugins/ui/ui.spinner.min.js"></script>
        <script src="/assets/plugins/datables/jquery.dataTables.min.js"></script>
        <script src="/assets/plugins/jquery.metadata.js"></script>
        <script src="/assets/plugins/sparkline.js"></script>
        <script src="/assets/plugins/progressbar.js"></script>
        <script src="/assets/plugins/feedback.js"></script>
        <script src="/assets/plugins/tipsy/jquery.tipsy.js"></script>
        <script src="/assets/plugins/jquery.maskedinput-1.3.min.js"></script>
        <script src="/assets/plugins/jquery.validate.min.js"></script>
        <script src="/assets/plugins/validationEngine/languages/jquery.validationEngine-en.js"></script>
        <script src="/assets/plugins/validationEngine/jquery.validationEngine.js"></script>
        <script src="/assets/plugins/jquery.elastic.js"></script>
        <script src="/assets/plugins/elrte/elrte.min.js"></script>
        <script src="/assets/plugins/miniColors/jquery.miniColors.min.js"></script>
        <script src="/assets/plugins/fullCalendar/fullcalendar.min.js"></script>
        <script src="/assets/plugins/elfinder/elfinder.min.js"></script>
        <script src="/assets/plugins/jquery.modal.js"></script>
        <script src="/assets/plugins/shadowbox/shadowbox.js"></script>
        <!-- chart -->
        <script src="/assets/plugins/jqPlot/jquery.jqplot.min.js"></script>
        <script src="/assets/plugins/jqPlot/plugins/jqplot.cursor.min.js"></script>
        <script src="/assets/plugins/jqPlot/plugins/jqplot.highlighter.min.js"></script>
        <script src="/assets/plugins/jqPlot/plugins/jqplot.barRenderer.min.js"></script>
        <script src="/assets/plugins/jqPlot/plugins/jqplot.pointLabels.min.js"></script>
        <!-- /chart -->
                
        <link rel="shortcut icon" href="../images/favicon.png">
    </head>
    <body>
        <div id="wrapper" class="container_12">

            <!-- # Sidebar -->
            <aside id="sidebar">
                <!-- Logo -->
                <div id="logo">
                    <img src="../images/logo-small.png" alt="Esplendido - Logo" />
                </div>
                <!-- /Logo -->

                <!-- Me bar -->
                <div id="me" class="secondary-widget">
                    <figure>
                        <img src="../images/me.png" alt="Sterling - Avatar" />
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
                            <li>
                                <a href="dashboard.html" title="Dashboard" data-icon="cloud">
                                    Dashboard
                                </a>
                            </li>
                            <li class="active">
                                <a href="form.html" title="Form" data-icon="archive">
                                    Forms
                                </a>
                            </li>
                            <li>
                                <a href="detailed-list.html" title="Detailed List" data-icon="list-with-icons">
                                    Detailed list
                                </a>
                            </li>
                            <li class="with-submenu">
                                <a href="#" title="UI Elements" data-icon="folder">
                                    UI Elements
                                </a>
                                <nav>
                                    <ul>
                                        <li><a href="widgets.html" title="Widgets">Widgets</a></li>
                                        <li><a href="icons.html" title="Icons">Icons</a></li>
                                        <li><a href="notifications.html" title="Notifications">Notifications</a></li>
                                        <li><a href="buttons.html" title="Buttons">Buttons</a></li>
                                        <li><a href="tables.html" title="Tables">Tables</a></li>
                                        <li><a href="miscellaneous.html" title="Miscellaneous">Miscellaneous</a></li>
                                    </ul>
                                </nav>
                            </li>
                            <li>
                                <a href="calendar.html" title="Calendar" data-icon="clock">
                                    Calendar
                                </a>
                            </li>
                            <li>
                                <a href="file-manager.html" title="File Manager" data-icon="terminal">
                                    File Manager
                                </a>
                            </li>
                            <li>
                                <a href="gallery.html" title="Gallery" data-icon="image">
                                    Gallery
                                </a>
                            </li>
                            <li>
                                <a href="typography.html" title="Typography" data-icon="text">
                                    Typography
                                </a>
                            </li>
                            <li>
                                <a href="error-page.html" title="Error page" data-icon="close-2">
                                    Error page
                                </a>
                            </li>
                            <li>
                                <a href="grid-system.html" title="Grid System" data-icon="html-code">
                                    Grid System
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
            
            <!-- # Main section -->
            <section id="main">
                <!-- QuickActions section -->
                <section id="quick-actions" class="quick-actions grid_12">
                    <nav>
                        <ul>
                                                        <li>
                                <a href="#myModal" title="Open modal" class="modal">
                                    <span class="glyph open-in-new-window"></span>
                                    Modal
                                </a>
                            </li>
                        </ul>
                    </nav>
                </section>
                
                <!-- Content section -->
                <section id="content">

                    <header class="pagetitle grid_12">
                        <h1>Dashboard</h1>
                        <nav class="breadcrumbs">
                            <ul>
                                <li><a href="#">Bread</a></li>
                                <li><a href="#">Crumbs</a></li>
                                <li><a href="#">Here</a></li>
                            </ul>
                        </nav>
                    </header>
                    
                    <!-- Widget -->
                    <div class="">
                        <div id="myModal" class="widget grid_6" hidden>
                            <header>
                                <div class="icon">
                                    <span class="icon" data-icon="applications-stack"></span>
                                </div>
                                
                                <div class="title">
                                    <h2>Modal</h2>
                                </div>
                            </header>
                            <div class="content">
                                <div class="inner">
                                    <p>Lorem ipsum tempus consectetur porttitor egestas sed eleifend eget tincidunt pharetra, varius tincidunt morbi malesuada elementum mi torquent mollis eu lobortis curae, purus amet vivamus amet nulla torquent nibh eu diam. aliquam pretium donec aliquam tempus lacus tempus feugiat lectus cras non velit mollis, sit et integer egestas habitant auctor integer sem at nam. massa himenaeos netus vel, dapibus nibh. </p>
                                </div>
                                <footer class="pane">
                                    <a href="#" class="close bt red">Close</a>
                                </footer>
                            </div>
                        </div>
                    </div>
                    <!-- /Widget -->                    

                    
                    <!-- Widget -->
                    <div class="grid_12">
                        <div class="widget minimizable">
                            <header>
                                <div class="icon">
                                    <span class="icon" data-icon="ui-text-field-medium"></span>
                                </div>

                                <div class="title">
                                    <h2>Form</h2>
                                </div>
                            </header>

                            <div class="content" >
                                <form action="#" class="validate">
                                    <fieldset class="set">
                                        <div class="field">
                                            <label>Text: </label>
                                            <div class="entry">
                                                <input type="text" class="required" name="text" />
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Text with icon: </label>
                                            <div class="entry with-helper">
                                                <div class="helper">
                                                    <span class="icon" data-icon="user"></span>
                                                </div>
                                                <input type="text" class="required" name="text-with-icon" />
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>File: </label>
                                            <div class="entry">
                                                <input type="file" class="required" name="file" />
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>File: <span>(browser default)</span></label>
                                            <div class="entry">
                                                <input type="file" class="no-ui required" name="file-browser" />
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Textarea:</label>
                                            <div class="entry">
                                                <textarea class="required" name="textarea"></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Dual text box: </label>
                                            <div class="entry">
                                                <div class="dual">
                                                    <input type="text" placeholder="First name" class="required" name="dualtext1" />
                                                    <input type="text" placeholder="Last name" class="required" name="dualtext2" />
                                                </div>
                                            </div>
                                        </div>                                        
                                        <div class="field">
                                            <label>Error: </label>
                                            <div class="entry error-container">
                                                <input type="text" />
                                                <div class="errors">
                                                    <label>Error 1</label>
                                                    <label>Error 2</label>
                                                    <label>Error 3</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label>Colorpicker: </label>
                                            <div class="entry tiny">
                                                <input type="text" class="colorpicker" />
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label>Datepicker: </label>
                                            <div class="entry with-helper">
                                                <div class="helper">
                                                    <span class="icon" data-icon="calendar"></span>
                                                </div>
                                                <input class="datepicker required" type="text" name="datepicker" />
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Datepicker: <span>(inline)</span></label>
                                            <div class="entry">
                                                <input class="datepicker inline" type="text" />
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Elastic textarea:</label>
                                            <div class="entry">
                                                <textarea class="elastic required" name="elastic-textarea"></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Select: <span>(default)</span></label>
                                            <div class="entry">
                                                <select class="required" name="select-default">
                                                    <option value="">Select an option</option>
                                                    <option>Option 1</option>
                                                    <option>Option 2</option>
                                                    <option>Option 3</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Select: <span>(spinner-skin)</span></label>
                                            <div class="entry small">
                                                <select class="required spinner-skin" name="select-spinner">
                                                    <option value="">Select an option</option>
                                                    <option>Option 1</option>
                                                    <option>Option 2</option>
                                                    <option>Option 3</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Select: <span>(browser default)</span></label>
                                            <div class="entry">
                                                <select class="required no-ui" name="select-browser">
                                                    <option value="">Select an option</option>
                                                    <option>Option 1</option>
                                                    <option>Option 2</option>
                                                    <option>Option 3</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Select: <span>(chosen plugin)</span></label>
                                            <div class="entry">
                                                <select class="required chosen" name="select-chosen">
                                                    <option value="">Select an option</option>
                                                    <option>Option 1</option>
                                                    <option>Option 2</option>
                                                    <option>Option 3</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Select multiple: <span>(chosen plugin)</span></label>
                                            <div class="entry">
                                                <select class="chosen {minlength:2}" name="select-multiple-chosen" multiple>
                                                    <option selected>Option 1</option>
                                                    <option>Option 2</option>
                                                    <option>Option 3</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <select class="multiselect {minlength:2}" multiple>
                                                <option selected>Option 1</option>
                                                <option>Option 2</option>
                                                <option>Option 3</option>
                                            </select>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Spinner:</label>
                                            <div class="entry tiny">
                                                <input type="text" class="spinner {min:2}" name="spinner" />
                                            </div>
                                        </div>
                                        
                                        <div class="heading">
                                            <h3>Heading</h3>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Checkbox: <span>(default skin)</span></label>
                                            <div class="check-list">
                                                <label><input type="checkbox" name="group[]" class="{minlength:2,messages:{minlength:'Check at least 2'}}" /> Checkbox</label>
                                                <label><input type="checkbox" checked name="group[]" /> Checked</label>
                                                <label><input type="checkbox" checked disabled /> Disabled Checked</label>
                                                <label><input type="checkbox" disabled /> Disabled</label>
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Radio: <span>(default skin)</span></label>
                                            <div class="check-list">
                                                <label><input type="radio" name="name" class="required" /> Radiobox</label>
                                                <label><input type="radio" name="name" /> Radiobox</label>
                                                <label><input type="radio" checked disabled /> Disabled Checked</label>
                                                <label><input type="radio" disabled  /> Disabled</label>
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Checkbox: <span>(grey skin)</span></label>
                                            <div class="check-list grey-skin">
                                                <label><input type="checkbox" name="group2[]" class="{minlength:2,messages:{minlength:'Check at least 2'}}" /> Checkbox</label>
                                                <label><input type="checkbox" checked name="group2[]" /> Checked</label>
                                                <label><input type="checkbox" checked disabled /> Disabled Checked</label>
                                                <label><input type="checkbox" disabled /> Disabled</label>
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Radio: <span>(grey skin)</span></label>
                                            <div class="check-list grey-skin">
                                                <label><input type="radio" name="name2" class="required" /> Radiobox</label>
                                                <label><input type="radio" name="name2" /> Radiobox</label>
                                                <label><input type="radio" checked disabled /> Disabled Checked</label>
                                                <label><input type="radio" disabled /> Disabled</label>
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Checkbox: <span>(button skin)</span></label>
                                            <div class="check-list button-skin">
                                                <label><input type="checkbox" name="group3[]" /> Checkbox</label>
                                                <label><input type="checkbox" checked name="group3[]" /> Checked</label>
                                                <label><input type="checkbox" checked disabled /> Disabled Checked</label>
                                                <label><input type="checkbox" disabled /> Disabled</label>
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Radio: <span>(button skin)</span></label>
                                            <div class="check-list button-skin">
                                                <label><input type="radio" name="name3" /> Radiobox</label>
                                                <label><input type="radio" name="name3" checked /> Checked</label>
                                                <label><input type="radio" checked disabled /> Disabled Checked</label>
                                                <label><input type="radio" disabled /> Disabled</label>
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Checkbox: <span>(browser skin)</span></label>
                                            <div class="check-list no-ui">
                                                <label><input type="checkbox" name="group4[]" class="{minlength:2,messages:{minlength:'Check at least 2'}}" /> Checkbox</label>
                                                <label><input type="checkbox" checked name="group4[]" /> Checked</label>
                                                <label><input type="checkbox" checked disabled /> Disabled Checked</label>
                                                <label><input type="checkbox" disabled /> Disabled</label>
                                            </div>
                                        </div>
                                        
                                        <div class="field">
                                            <label>Radio: <span>(browser skin)</span></label>
                                            <div class="check-list no-ui">
                                                <label><input type="radio" name="name4" class="required" /> Radiobox</label>
                                                <label><input type="radio" name="name4" /> Radiobox</label>
                                                <label><input type="radio" checked disabled /> Disabled Checked</label>
                                                <label><input type="radio" disabled /> Disabled</label>
                                            </div>
                                        </div>
                                    </fieldset>
                                    <footer class="pane">
                                        <input type="submit" value="Click to validate them all!" class="fullpane-bt" />
                                    </footer>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- /Widget -->
                    
                    <!-- Widget -->
                    <div class="grid_12">
                        <div class="widget wizard">
                            <header>
                                <div class="title">
                                    <h2>Wizard</h2>
                                    <span>This is a wizard example</span>
                                </div>
                                <nav class="steps">
                                    <ul>
                                        <li class="active">
                                            <div>1</div>
                                            <span>Installation</span>
                                        </li>
                                        <li>
                                            <div>2</div>
                                            <span>Backup</span>
                                        </li>
                                        <li>
                                            <div>3</div>
                                            <span>Finish</span>
                                        </li>
                                    </ul>
                                </nav>
                            </header>
                            <div class="content">
                                <fieldset class="set">
                                    <div class="field">
                                        <label>Text: </label>
                                        <div class="entry">
                                            <input type="text" />
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label>Textarea: </label>
                                        <div class="entry">
                                            <textarea></textarea>
                                        </div>
                                    </div>
                                </fieldset>
                                <footer class="pane">
                                    <a class="bt blue" href="#">A button</a>
                                </footer>
                            </div>
                        </div>
                    </div>
                    <!-- /Widget -->
                                
                    <!-- Widget -->
                    <div class="grid_6">
                        <div class="widget minimizable">
                            <header>
                                <div class="icon">
                                    <span class="icon" data-icon="ui-text-area"></span>
                                </div>

                                <div class="title">
                                    <h2>WYSIWYG</h2>
                                </div>
                            </header>

                            <div class="content">
                                <textarea class="editor"></textarea>
                            </div>
                        </div>
                    </div>
                    <!-- /Widget -->
                    
                    <!-- Widget -->
                    <div class="grid_6">
                        <div class="widget minimizable">
                            <header>
                                <div class="icon">
                                    <span class="icon" data-icon="arrow-in-out"></span>
                                </div>

                                <div class="title">
                                    <h2>Sizes</h2>
                                </div>
                            </header>

                            <div class="content">
                                <fieldset class="set">
                                    <div class="field">
                                        <label>Text: <span>(full-width)</span></label>
                                        <div class="entry">
                                            <input type="text" />
                                        </div>
                                    </div>
                                    
                                    <div class="field">
                                        <label>Text: <span>(big)</span></label>
                                        <div class="entry big">
                                            <input type="text" />
                                        </div>
                                    </div>
                                    
                                    <div class="field">
                                        <label>Text: <span>(medium)</span></label>
                                        <div class="entry medium">
                                            <input type="text" />
                                        </div>
                                    </div>
                                    
                                    <div class="field">
                                        <label>Text: <span>(small)</span></label>
                                        <div class="entry small">
                                            <input type="text" name="text" class="required" />
                                        </div>
                                    </div>
                                    
                                    <div class="field">
                                        <label>Text: <span>(tiny)</span></label>
                                        <div class="entry tiny">
                                            <input type="text" name="text" class="required" />
                                        </div>
                                    </div>
                                </fieldset>
                                
                                <footer class="pane">
                                    <div class="entry medium with-helper">
                                        <div class="helper">
                                            <input type="checkbox" />
                                        </div>
                                        <select>
                                            <option>Bulk action</option>
                                            <option>Option 2</option>
                                            <option>Option 3</option>
                                        </select>
                                    </div>
                                    <input type="submit" class="bt orange" value="Submit button" />
                                </footer>
                            </div>
                        </div>
                    </div>
                    <!-- /Widget -->

                    <!-- Widget -->
                    <div class="grid_12">
                        <div class="widget minimizable">
                            <header>
                                <div class="icon">
                                    <span class="icon" data-icon="ui-text-field-format"></span>
                                </div>

                                <div class="title">
                                    <h2>Masks</h2>
                                </div>
                            </header>

                            <div class="content">
                                <fieldset class="set">
                                    <div class="field">
                                        <label for="text-input-m-date">Date mask: <span>99/99/9999</span></label>
                                        <div class="entry">
                                            <input type="text" id="text-input-m-date" class="mask-date" />
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label for="text-input-m-phone">Phone: <span>(999) 999-9999</span></label>
                                        <div class="entry">
                                            <input type="text" id="text-input-m-phone" class="mask-phone" />
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label for="text-input-m-phoneext">Phone + Ext: <span>(999) 999-9999? x99999</span></label>
                                        <div class="entry">
                                            <input type="text" id="text-input-m-phoneext" class="mask-phoneext" />
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label for="text-input-m-taxid">Tax ID: <span>99-9999999</span></label>
                                        <div class="entry">
                                            <input type="text" id="text-input-m-taxid" class="mask-tin" />
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label for="text-input-m-ssn">SSN: <span>999-99-9999</span></label>    
                                        <div class="entry">
                                            <input type="text" id="text-input-m-ssn" class="mask-ssn" />
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label for="text-input-pk">Product key: <span>a*-999-a999</span></label>
                                        <div class="entry">
                                            <input type="text" id="text-input-pk" class="mask-product" />
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label for="text-input-m-eyescript">Eye Script: <span>~9.99 ~9.99 999</span></label>
                                        <div class="entry">
                                            <input type="text" id="text-input-m-eyescript" class="mask-eyescript" />
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </div>
                    <!-- /Widget -->
                    
                </section>
                <!-- /Content section -->
                
            </section>
            <!-- /Main section -->
        </div>
    </body>
</html>
{/literal}