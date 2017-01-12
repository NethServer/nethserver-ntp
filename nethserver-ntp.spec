Name: nethserver-ntp
Summary: NethServer specific Chrony configuration files and templates
Version: 1.1.1
Release: 1%{?dist}
License: GPL
Source: %{name}-%{version}.tar.gz
BuildArch: noarch
BuildRequires: nethserver-devtools
URL: %{url_prefix}/%{name} 

Requires: nethserver-base
Requires: chrony
Requires: perl(NethServer::Database::Timedate)

%description
Configuration files and templates for the Chrony daemon.

%prep
%setup

%build
%{makedocs}
perl createlinks

%install
rm -rf %{buildroot}
(cd root; find . -depth -print | cpio -dump %{buildroot})
%{genfilelist} %{buildroot} > %{name}-%{version}-filelist

%files -f %{name}-%{version}-filelist
%defattr(-,root,root)
%dir %{_nseventsdir}/%{name}-update
%doc COPYING
%doc README.rst

%changelog
* Thu Jan 12 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.1.1-1
- Avoid clock adjust false error condition - NethServer/nethserver-ntp#6
- Fix warnings on migration

* Thu Jul 07 2016 Stefano Fancello <stefano.fancello@nethesis.it> - 1.1.0-1
- First NS7 release

* Tue Sep 29 2015 Davide Principi <davide.principi@nethesis.it> - 1.0.9-1
- Make Italian language pack optional - Enhancement #3265 [NethServer]

* Tue Mar 03 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.0.8-1
- Set PHP default timezone from system timezone - Enhancement #3068 [NethServer]
- Date and time panel Save button - Bug #3023 [NethServer]
- Base: first configuration wizard - Feature #2957 [NethServer]

* Tue Dec 09 2014 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.0.7-1.ns6
- Drop TCP wrappers hosts.allow hosts.deny templates - Enhancement #2785 [NethServer]

* Wed Feb 05 2014 Davide Principi <davide.principi@nethesis.it> - 1.0.6-1.ns6
- NethCamp 2014 - Task #2618 [NethServer]
- ntpd config db entry is missing UDPPort default property - Bug #2505 [NethServer]
- Update all inline help documentation - Task #1780 [NethServer]

* Thu Oct 17 2013 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.0.5-1.ns6
- Add language code to URLs #2113
- Db defaults: remove Runlevels property #2067

* Tue Jun  4 2013 Davide Principi <davide.principi@nethesis.it> - 1.0.4-1.ns6
- Force ntpd syncronization with iburst option. Enhancement #1964 

* Thu May 30 2013 Davide Principi <davide.principi@nethesis.it> - 1.0.3-1.ns6
- NTP Server validation failed. Fixed label translations. Bug #1988

* Tue Apr 30 2013 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.0.2-1.ns6
- Rebuild for automatic package handling. #1870

* Tue Mar 19 2013 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.0.1-1
- First release

