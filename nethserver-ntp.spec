Name: nethserver-ntp
Summary: NethServer specific NTP configuration files and templates
Version: 1.0.7
Release: 1
License: GPL
Source: %{name}-%{version}.tar.gz
BuildArch: noarch
BuildRequires: nethserver-devtools
URL: %{url_prefix}/%{name} 
AutoReq: no

Requires: nethserver-base
Requires: ntp

%description
Configuration files and templates for the NTP daemon.

%prep
%setup

%build
%{makedocs}
perl createlinks

%install
rm -rf $RPM_BUILD_ROOT
(cd root   ; find . -depth -print | cpio -dump $RPM_BUILD_ROOT)
%{genfilelist} \
    $RPM_BUILD_ROOT > %{name}-%{version}-filelist
echo "%doc COPYING"          >> %{name}-%{version}-filelist

%clean 
rm -rf $RPM_BUILD_ROOT

%post

%files -f %{name}-%{version}-filelist
%defattr(-,root,root)


%changelog
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

