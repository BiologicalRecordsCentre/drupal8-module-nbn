# drupal8-module-nbn
Module for Drupal 8 that allows information from NBN web services to be added to panel or display suit.

Requirements are a Drupal 8 site installed. When adding a block via "Place block", select "NBN service" from the list of block and add in to perticular region. you add same block on the Panels too. The module adds a group of components under an NBN section, which can be selected and added to the page. Each component will make calls to NBN web services and the returned results are themed and presented in the panel or on the page. The settings for each component list all possible fields that can be displayed from which the user may select those they wish to appear. They also list options for theming. The module is comprised of

nbn.module which contains theme hook in to Drupal
NbnServiceBlock will fecilitate to add block anywhere in the panel or region
NBNClientController which contains all the calls to the web services
/templates which contains information for different ways to theme the output
