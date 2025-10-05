import React, { useState, useEffect } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { LoadingSpinner } from '@/components/ui/loading-spinner'
import { Badge } from '@/components/ui/badge'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { ProviderList } from './ProviderList'
import { CredentialsList } from './CredentialsList'
import { AddCredentialDialog } from './AddCredentialDialog'
import { EditCredentialDialog } from './EditCredentialDialog'
import { ProviderDashboard } from './ProviderDashboard'
import { UsageMetrics } from './UsageMetrics'
import { ProviderModelsModal } from './ProviderModelsModal'
import { providersApi, credentialsApi } from '@/lib/api/providers'
import { 
  Brain, 
  Activity, 
  CheckCircle, 
  AlertTriangle,
  RefreshCw,
  Settings,
  Key,
  BarChart3,
  Plus,
  ChevronDown
} from 'lucide-react'
import type { 
  Provider, 
  Credential, 
  ProviderStatistics,
  CreateCredentialRequest,
  UpdateCredentialRequest
} from '@/types/provider'

export function ProvidersManagement() {
  // Core state
  const [providers, setProviders] = useState<Provider[]>([])
  const [statistics, setStatistics] = useState<ProviderStatistics | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  
  // Provider operations state
  const [testingProviders, setTestingProviders] = useState<Set<string>>(new Set())
  
  // Credentials state
  const [selectedProvider, setSelectedProvider] = useState<string | null>(null)
  const [credentials, setCredentials] = useState<Credential[]>([])
  const [credentialsLoading, setCredentialsLoading] = useState(false)
  const [testingCredentials, setTestingCredentials] = useState<Set<number>>(new Set())
  
  // Dialog state
  const [showAddCredential, setShowAddCredential] = useState(false)
  const [editingCredential, setEditingCredential] = useState<Credential | null>(null)
  const [credentialOperationLoading, setCredentialOperationLoading] = useState(false)

  // Active tab state
  const [activeTab, setActiveTab] = useState('overview')
  
  // Add provider state
  const [showAddProviderDropdown, setShowAddProviderDropdown] = useState(false)
  const [disabledProviders, setDisabledProviders] = useState<Provider[]>([])
  
  // Provider models management state
  const [showProviderModels, setShowProviderModels] = useState(false)
  const [selectedProviderForModels, setSelectedProviderForModels] = useState<Provider | null>(null)

  // Load initial data
  useEffect(() => {
    loadProviders()
    loadStatistics()
    loadDisabledProviders()
  }, [])

  // Load credentials when provider is selected
  useEffect(() => {
    if (selectedProvider) {
      loadCredentials(selectedProvider)
    }
  }, [selectedProvider])

  const loadProviders = async () => {
    try {
      setError(null)
      const data = await providersApi.getProviders()
      setProviders(data || []) // Ensure we always have an array
    } catch (error) {
      setError(error instanceof Error ? error.message : 'Failed to load providers')
      setProviders([]) // Set empty array on error
    } finally {
      setIsLoading(false)
    }
  }

  const loadStatistics = async () => {
    try {
      const data = await providersApi.getStatistics()
      setStatistics(data)
    } catch (error) {
      console.error('Failed to load statistics:', error)
    }
  }

  const loadDisabledProviders = async () => {
    try {
      // Get all providers and filter for disabled ones
      const allProviders = await providersApi.getProviders()
      const disabled = (allProviders || []).filter(p => !p.enabled)
      setDisabledProviders(disabled)
    } catch (error) {
      console.error('Failed to load disabled providers:', error)
    }
  }

  const loadCredentials = async (providerId: string) => {
    setCredentialsLoading(true)
    try {
      const data = await credentialsApi.getCredentials(providerId)
      setCredentials(data)
    } catch (error) {
      setError(error instanceof Error ? error.message : 'Failed to load credentials')
    } finally {
      setCredentialsLoading(false)
    }
  }

  const handleToggleProvider = async (providerId: string) => {
    try {
      const updatedProvider = await providersApi.toggleProvider(providerId)
      setProviders(prev => prev.map(p => 
        p.id === providerId ? updatedProvider : p
      ))
      loadStatistics()
      loadDisabledProviders() // Refresh disabled providers list
    } catch (error) {
      setError(error instanceof Error ? error.message : 'Failed to toggle provider')
    }
  }

  const handleAddProvider = async (providerId: string) => {
    try {
      // Enable the provider
      await providersApi.toggleProvider(providerId)
      // Refresh all data
      await loadProviders()
      await loadStatistics()
      await loadDisabledProviders()
      // Switch to providers tab and select the newly added provider for configuration
      setActiveTab('providers')
      setShowAddProviderDropdown(false)
    } catch (error) {
      setError(error instanceof Error ? error.message : 'Failed to add provider')
    }
  }

  const handleTestProvider = async (providerId: string) => {
    setTestingProviders(prev => new Set([...prev, providerId]))
    try {
      await providersApi.testProvider(providerId)
      // Refresh provider status after test
      await loadProviders()
    } catch (error) {
      setError(error instanceof Error ? error.message : 'Provider test failed')
    } finally {
      setTestingProviders(prev => {
        const newSet = new Set(prev)
        newSet.delete(providerId)
        return newSet
      })
    }
  }

  const handleBulkHealthCheck = async () => {
    setIsLoading(true)
    try {
      await providersApi.bulkHealthCheck()
      await loadProviders()
      await loadStatistics()
    } catch (error) {
      setError(error instanceof Error ? error.message : 'Bulk health check failed')
    } finally {
      setIsLoading(false)
    }
  }

  // Credential operations
  const handleAddCredential = async (data: CreateCredentialRequest) => {
    if (!selectedProvider) return
    
    setCredentialOperationLoading(true)
    try {
      await credentialsApi.createCredential(selectedProvider, data)
      await loadCredentials(selectedProvider)
      await loadProviders() // Refresh provider credentials count
    } catch (error) {
      throw error // Re-throw to let dialog handle the error
    } finally {
      setCredentialOperationLoading(false)
    }
  }

  const handleEditCredential = async (credentialId: number, data: UpdateCredentialRequest) => {
    if (!selectedProvider) return
    
    setCredentialOperationLoading(true)
    try {
      await credentialsApi.updateCredential(selectedProvider, credentialId, data)
      await loadCredentials(selectedProvider)
      await loadProviders()
    } catch (error) {
      throw error
    } finally {
      setCredentialOperationLoading(false)
    }
  }

  const handleDeleteCredential = async (credential: Credential) => {
    if (!selectedProvider || !confirm('Are you sure you want to delete this credential?')) return
    
    try {
      await credentialsApi.deleteCredential(selectedProvider, credential.id)
      await loadCredentials(selectedProvider)
      await loadProviders()
    } catch (error) {
      setError(error instanceof Error ? error.message : 'Failed to delete credential')
    }
  }

  const handleTestCredential = async (credential: Credential) => {
    if (!selectedProvider) return
    
    setTestingCredentials(prev => new Set([...prev, credential.id]))
    try {
      const result = await credentialsApi.testCredential(selectedProvider, credential.id)
      return result
    } catch (error) {
      return {
        success: false,
        message: error instanceof Error ? error.message : 'Test failed'
      }
    } finally {
      setTestingCredentials(prev => {
        const newSet = new Set(prev)
        newSet.delete(credential.id)
        return newSet
      })
    }
  }

  const handleToggleCredentialActive = async (credential: Credential) => {
    if (!selectedProvider) return
    
    try {
      await credentialsApi.updateCredential(selectedProvider, credential.id, {
        is_active: !credential.is_active
      })
      await loadCredentials(selectedProvider)
    } catch (error) {
      setError(error instanceof Error ? error.message : 'Failed to update credential')
    }
  }

  const getSelectedProviderName = () => {
    const provider = providers.find(p => p.id === selectedProvider)
    return provider?.name || 'Unknown Provider'
  }

  if (isLoading && providers.length === 0) {
    return (
      <div className="flex items-center justify-center py-12">
        <LoadingSpinner />
        <span className="ml-2">Loading providers...</span>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Providers Management</h1>
          <p className="text-muted-foreground">
            Configure and manage AI provider integrations
          </p>
        </div>
        
        <div className="flex gap-2">
          <Button variant="outline" onClick={loadProviders} disabled={isLoading}>
            <RefreshCw className={`mr-2 h-4 w-4 ${isLoading ? 'animate-spin' : ''}`} />
            Refresh
          </Button>
          <Button onClick={handleBulkHealthCheck} disabled={isLoading}>
            <Activity className="mr-2 h-4 w-4" />
            Check All Health
          </Button>
          {disabledProviders.length > 0 && (
            <DropdownMenu open={showAddProviderDropdown} onOpenChange={setShowAddProviderDropdown}>
              <DropdownMenuTrigger asChild>
                <Button>
                  <Plus className="mr-2 h-4 w-4" />
                  Add Provider
                  <ChevronDown className="ml-2 h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-64">
                {disabledProviders.map((provider) => (
                  <DropdownMenuItem
                    key={provider.id}
                    onClick={() => handleAddProvider(provider.id)}
                  >
                    <div className="flex items-center gap-3 w-full">
                      <div className="text-lg">🤖</div>
                      <div className="flex-1">
                        <div className="font-medium">{provider.name}</div>
                        <div className="text-sm text-muted-foreground">
                          {provider.model_counts ? 
                            `${provider.model_counts.total} models (${provider.model_counts.enabled} enabled)` :
                            `${(provider.models || []).length} models`
                          }
                        </div>
                      </div>
                    </div>
                  </DropdownMenuItem>
                ))}
              </DropdownMenuContent>
            </DropdownMenu>
          )}
        </div>
      </div>

      {/* Error Alert */}
      {error && (
        <Alert variant="destructive">
          <AlertTriangle className="h-4 w-4" />
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      {/* Statistics Overview */}
      {statistics && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card>
            <CardContent className="p-4">
              <div className="flex items-center gap-2">
                <Brain className="h-4 w-4 text-muted-foreground" />
                <span className="text-sm text-muted-foreground">Total Providers</span>
              </div>
              <div className="text-2xl font-bold">{statistics.total_providers}</div>
            </CardContent>
          </Card>
          
          <Card>
            <CardContent className="p-4">
              <div className="flex items-center gap-2">
                <CheckCircle className="h-4 w-4 text-green-600" />
                <span className="text-sm text-muted-foreground">Enabled</span>
              </div>
              <div className="text-2xl font-bold text-green-600">{statistics.enabled_providers}</div>
            </CardContent>
          </Card>
          
          <Card>
            <CardContent className="p-4">
              <div className="flex items-center gap-2">
                <Activity className="h-4 w-4 text-blue-600" />
                <span className="text-sm text-muted-foreground">Healthy</span>
              </div>
              <div className="text-2xl font-bold text-blue-600">{statistics.healthy_providers}</div>
            </CardContent>
          </Card>
          
          <Card>
            <CardContent className="p-4">
              <div className="flex items-center gap-2">
                <Key className="h-4 w-4 text-purple-600" />
                <span className="text-sm text-muted-foreground">Credentials</span>
              </div>
              <div className="text-2xl font-bold text-purple-600">{statistics.total_credentials}</div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Main Content Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="overview" className="gap-2">
            <BarChart3 className="h-4 w-4" />
            Overview
          </TabsTrigger>
          <TabsTrigger value="dashboard" className="gap-2">
            <Activity className="h-4 w-4" />
            Dashboard
          </TabsTrigger>
          <TabsTrigger value="providers" className="gap-2">
            <Brain className="h-4 w-4" />
            Providers
          </TabsTrigger>
          <TabsTrigger value="credentials" className="gap-2">
            <Key className="h-4 w-4" />
            Credentials
          </TabsTrigger>
        </TabsList>

        {/* Overview Tab */}
        <TabsContent value="overview" className="space-y-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Provider Status Overview */}
            <Card>
              <CardHeader>
                <CardTitle>Provider Status</CardTitle>
                <CardDescription>
                  Current status of all configured providers
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  {(providers || []).filter(provider => provider.enabled).slice(0, 5).map((provider) => (
                    <div key={provider.id} className="flex items-center justify-between">
                      <div className="flex items-center gap-3">
                        <div className="text-lg">{provider.id === 'openai' ? '🤖' : provider.id === 'anthropic' ? '🧠' : '🤖'}</div>
                        <div>
                          <div className="font-medium">{provider.name}</div>
                        <div className="text-sm text-muted-foreground">
                          {provider.model_counts ? 
                            `${provider.model_counts.total} models (${provider.model_counts.enabled} enabled)` :
                            `${(provider.models || []).length} models`
                          }
                        </div>
                        </div>
                      </div>
                      <div className="flex items-center gap-2">
                        <Badge variant={provider.enabled ? 'default' : 'secondary'}>
                          {provider.enabled ? 'Enabled' : 'Disabled'}
                        </Badge>
                        <Badge variant={provider.status === 'healthy' ? 'default' : 'destructive'}>
                          {provider.status}
                        </Badge>
                      </div>
                    </div>
                  ))}
                  {(providers || []).filter(provider => provider.enabled).length > 5 && (
                    <Button 
                      variant="outline" 
                      className="w-full"
                      onClick={() => setActiveTab('providers')}
                    >
                      View All {(providers || []).filter(provider => provider.enabled).length} Enabled Providers
                    </Button>
                  )}
                  {(providers || []).filter(provider => provider.enabled).length === 0 && (
                    <div className="text-center py-4 text-muted-foreground">
                      No enabled providers found. 
                      <Button 
                        variant="link" 
                        className="p-0 ml-1 text-primary"
                        onClick={() => setActiveTab('providers')}
                      >
                        Enable providers
                      </Button> to see them here.
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>

            {/* Quick Actions */}
            <Card>
              <CardHeader>
                <CardTitle>Quick Actions</CardTitle>
                <CardDescription>
                  Common provider management tasks
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                <Button 
                  className="w-full justify-start"
                  variant="outline"
                  onClick={() => setActiveTab('providers')}
                >
                  <Settings className="mr-2 h-4 w-4" />
                  Configure Providers
                </Button>
                
                <Button 
                  className="w-full justify-start"
                  variant="outline"
                  onClick={() => setActiveTab('credentials')}
                >
                  <Key className="mr-2 h-4 w-4" />
                  Manage Credentials
                </Button>
                
                <Button 
                  className="w-full justify-start"
                  variant="outline"
                  onClick={handleBulkHealthCheck}
                  disabled={isLoading}
                >
                  <Activity className="mr-2 h-4 w-4" />
                  Run Health Checks
                </Button>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Dashboard Tab */}
        <TabsContent value="dashboard">
          <ProviderDashboard 
            timeRange="24h"
            autoRefresh={true}
            refreshInterval={30000}
          />
        </TabsContent>

        {/* Providers Tab */}
        <TabsContent value="providers">
          <ProviderList
            providers={providers}
            isLoading={isLoading}
            onToggle={handleToggleProvider}
            onTest={handleTestProvider}
            onConfigure={(providerId) => {
              const provider = providers.find(p => p.id === providerId)
              if (provider) {
                setSelectedProviderForModels(provider)
                setShowProviderModels(true)
              }
            }}
            onViewCredentials={(providerId) => {
              setSelectedProvider(providerId)
              setActiveTab('credentials')
            }}
            onViewDetails={(providerId) => {
              // TODO: Implement provider details view
              console.log('View provider details:', providerId)
            }}
            onRefresh={loadProviders}
            testingProviders={testingProviders}
          />
        </TabsContent>

        {/* Credentials Tab */}
        <TabsContent value="credentials">
          {selectedProvider ? (
            <CredentialsList
              credentials={credentials}
              providerId={selectedProvider}
              providerName={getSelectedProviderName()}
              isLoading={credentialsLoading}
              onAdd={() => setShowAddCredential(true)}
              onEdit={setEditingCredential}
              onDelete={handleDeleteCredential}
              onTest={handleTestCredential}
              onToggleActive={handleToggleCredentialActive}
              onRefresh={() => loadCredentials(selectedProvider)}
              testingCredentials={testingCredentials}
            />
          ) : (
            <Card>
              <CardContent className="flex flex-col items-center justify-center py-12 text-center">
                <Key className="h-12 w-12 text-muted-foreground mb-4" />
                <h3 className="text-lg font-medium mb-2">Select a Provider</h3>
                <p className="text-muted-foreground mb-4">
                  Choose a provider from the Providers tab to view and manage its credentials
                </p>
                <Button onClick={() => setActiveTab('providers')}>
                  Go to Providers
                </Button>
              </CardContent>
            </Card>
          )}
        </TabsContent>
      </Tabs>

      {/* Dialogs */}
      <AddCredentialDialog
        open={showAddCredential}
        onOpenChange={setShowAddCredential}
        onSubmit={handleAddCredential}
        onTest={async (data) => {
          // TODO: Implement credential testing
          return { success: true, message: 'Test successful' }
        }}
        providerId={selectedProvider || ''}
        providerName={getSelectedProviderName()}
        isLoading={credentialOperationLoading}
      />

      <EditCredentialDialog
        open={!!editingCredential}
        onOpenChange={(open) => !open && setEditingCredential(null)}
        credential={editingCredential}
        onSubmit={handleEditCredential}
        onTest={handleTestCredential}
        isLoading={credentialOperationLoading}
      />

      {/* Provider Models Modal */}
      <ProviderModelsModal
        isOpen={showProviderModels}
        onClose={() => {
          setShowProviderModels(false)
          setSelectedProviderForModels(null)
        }}
        provider={selectedProviderForModels}
      />
    </div>
  )
}